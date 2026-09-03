from __future__ import annotations

import hashlib
import base64
import json
import logging
import shutil
import signal
import threading
import time
from pathlib import Path

from .config import Settings
from .gateway import PortalGateway
from .models import JobManifest
from .processor import VideoFrameProcessor
from .state import JobState

logger = logging.getLogger(__name__)


class LeaseHeartbeat:
    def __init__(self, gateway: PortalGateway, job: JobManifest, interval: float) -> None:
        self.gateway = gateway
        self.job = job
        self.interval = interval
        self.finished = threading.Event()
        self.thread = threading.Thread(target=self._run, daemon=True, name=f"lease-{job.job_id}")

    def __enter__(self) -> "LeaseHeartbeat":
        self.thread.start()
        return self

    def __exit__(self, *_: object) -> None:
        self.finished.set()
        self.thread.join(timeout=min(self.interval, 5))

    def _run(self) -> None:
        while not self.finished.wait(self.interval):
            try:
                self.gateway.heartbeat(self.job)
            except Exception:
                logger.exception("heartbeat failed for job %s", self.job.job_id)


class Worker:
    def __init__(self, settings: Settings) -> None:
        self.settings = settings
        self.gateway = PortalGateway(settings)
        self.state = JobState(settings.runtime_dir / "worker.sqlite3")
        self.processor = VideoFrameProcessor(settings.ffmpeg, settings.ffprobe)
        self.stopping = False

    def stop(self, *_: object) -> None:
        self.stopping = True

    def run(self) -> None:
        signal.signal(signal.SIGINT, self.stop)
        signal.signal(signal.SIGTERM, self.stop)
        logger.info("worker %s started", self.settings.machine_id)
        while not self.stopping:
            try:
                job = self.gateway.claim()
                if job is None:
                    time.sleep(self.settings.poll_seconds)
                    continue
                self.process(job)
            except Exception:
                logger.exception("worker loop failed")
                time.sleep(self.settings.poll_seconds)

    def process(self, job: JobManifest) -> None:
        work_dir = self.settings.runtime_dir / "jobs" / job.job_id
        if work_dir.exists():
            shutil.rmtree(work_dir)
        work_dir.mkdir(parents=True)
        try:
            with LeaseHeartbeat(self.gateway, job, max(10, self.settings.lease_seconds / 3)):
                self.state.set(job.job_id, "downloading")
                self.gateway.progress(job, 5, "downloading")
                source = work_dir / "source.video"
                self.gateway.download(job, source)

                self.state.set(job.job_id, "processing")
                stage = "analyzing_video" if job.operation == "analyze_video" else "extracting_frames"
                self.gateway.progress(job, 20, stage)
                frames = work_dir / job.artifact_kind
                info = self.processor.analyze(source, frames) if job.operation == "analyze_video" else self.processor.extract(job, source, frames)
                metadata = {
                    "schema": 1,
                    "job_id": job.job_id,
                    "source": {"width": info.width, "height": info.height, "frame_count": info.frame_count, "fps": info.fps, "duration_seconds": info.duration_seconds},
                    "selection": ({"start": job.selection.start, "end": job.selection.end, "step": job.selection.step} if job.selection else None),
                    "frames": [path.name for path in sorted(frames.glob("*.jpg"))],
                }
                (frames / "manifest.json").write_text(json.dumps(metadata, indent=2), encoding="utf-8")
                archive_base = work_dir / "frames"
                archive = Path(shutil.make_archive(str(archive_base), "zip", frames))

                self.state.set(job.job_id, "uploading")
                self.gateway.progress(job, 90, "uploading")
                self.gateway.upload(job, archive)
                with archive.open("rb") as stream:
                    digest = hashlib.file_digest(stream, "sha256").hexdigest()
                result = None
                if job.operation == "analyze_video":
                    result = {
                        "video": metadata["source"],
                        "thumbnails": [base64.b64encode(path.read_bytes()).decode("ascii") for path in sorted(frames.glob("thumbnail_*.jpg"))],
                    }
                self.gateway.complete(job, digest, archive.stat().st_size, result)
                self.state.set(job.job_id, "completed")
        except Exception as exc:
            self.state.set(job.job_id, "failed", str(exc))
            try:
                self.gateway.fail(job, type(exc).__name__, str(exc))
            except Exception:
                logger.exception("could not report failure for job %s", job.job_id)
            raise
        finally:
            shutil.rmtree(work_dir, ignore_errors=True)
