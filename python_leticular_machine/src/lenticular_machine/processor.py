from __future__ import annotations

import json
import shutil
import subprocess
from dataclasses import dataclass
from pathlib import Path

from .models import JobManifest


class ProcessingError(RuntimeError):
    pass


@dataclass(frozen=True, slots=True)
class VideoInfo:
    width: int
    height: int
    frame_count: int
    fps: float
    duration_seconds: float


class VideoFrameProcessor:
    def __init__(self, ffmpeg: str = "ffmpeg", ffprobe: str = "ffprobe") -> None:
        self.ffmpeg = ffmpeg
        self.ffprobe = ffprobe

    def ensure_available(self) -> None:
        for binary in (self.ffmpeg, self.ffprobe):
            if shutil.which(binary) is None:
                raise ProcessingError(f"required executable is unavailable: {binary}")

    def probe(self, source: Path) -> VideoInfo:
        result = subprocess.run(
            [self.ffprobe, "-v", "error", "-select_streams", "v:0", "-count_frames",
             "-show_entries", "stream=width,height,nb_read_frames,nb_frames,avg_frame_rate,duration",
             "-of", "json", str(source)],
            capture_output=True, text=True, check=False, timeout=120,
        )
        if result.returncode != 0:
            raise ProcessingError(f"ffprobe rejected the video: {result.stderr.strip()}")
        try:
            stream = json.loads(result.stdout)["streams"][0]
            count = int(stream.get("nb_read_frames") or stream["nb_frames"])
            rate = stream["avg_frame_rate"].split("/")
            fps = float(rate[0]) / float(rate[1])
            return VideoInfo(width=int(stream["width"]), height=int(stream["height"]), frame_count=count, fps=fps, duration_seconds=float(stream["duration"]))
        except (KeyError, IndexError, TypeError, ValueError) as exc:
            raise ProcessingError("video metadata is incomplete") from exc

    def run_ffmpeg(self, command: list[str], timeout: int) -> subprocess.CompletedProcess[str]:
        result = subprocess.run(command, capture_output=True, text=True, check=False, timeout=timeout)
        if result.returncode != 0 and "Unrecognized option 'fps_mode'" in result.stderr:
            fallback = command.copy()
            index = fallback.index("-fps_mode")
            fallback[index:index + 2] = ["-vsync", "vfr"]
            result = subprocess.run(fallback, capture_output=True, text=True, check=False, timeout=timeout)
        return result

    def extract(self, manifest: JobManifest, source: Path, output_dir: Path) -> VideoInfo:
        self.ensure_available()
        info = self.probe(source)
        selection = manifest.selection
        if selection is None:
            raise ProcessingError("frame selection is required")
        if selection.end >= info.frame_count:
            raise ProcessingError(
                f"selected frame {selection.end} exceeds last frame {info.frame_count - 1}"
            )
        output_dir.mkdir(parents=True, exist_ok=True)
        expression = (
            f"between(n\\,{selection.start}\\,{selection.end})*"
            f"not(mod(n-{selection.start}\\,{selection.step}))"
        )
        qscale = round(31 - ((selection.jpeg_quality - 1) * 29 / 99))
        result = self.run_ffmpeg(
            [self.ffmpeg, "-nostdin", "-v", "error", "-i", str(source),
             "-vf", f"select='{expression}'", "-fps_mode", "vfr", "-q:v",
             str(qscale), str(output_dir / "frame_%06d.jpg")],
            timeout=3600,
        )
        if result.returncode != 0:
            raise ProcessingError(f"frame extraction failed: {result.stderr.strip()}")
        expected = ((selection.end - selection.start) // selection.step) + 1
        actual = len(list(output_dir.glob("frame_*.jpg")))
        if actual != expected:
            raise ProcessingError(f"expected {expected} frames, extracted {actual}")
        return info

    def analyze(self, source: Path, output_dir: Path) -> VideoInfo:
        self.ensure_available()
        info = self.probe(source)
        indices = sorted({0, (info.frame_count - 1) // 2, info.frame_count - 1})
        output_dir.mkdir(parents=True, exist_ok=True)
        expression = "+".join(f"eq(n\\,{index})" for index in indices)
        result = self.run_ffmpeg(
            [self.ffmpeg, "-nostdin", "-v", "error", "-i", str(source),
             "-vf", f"select='{expression}',scale='min(1280,iw)':-2", "-fps_mode", "vfr",
             "-q:v", "3", str(output_dir / "thumbnail_%02d.jpg")],
            timeout=600,
        )
        if result.returncode != 0:
            raise ProcessingError(f"thumbnail extraction failed: {result.stderr.strip()}")
        if len(list(output_dir.glob("thumbnail_*.jpg"))) != len(indices):
            raise ProcessingError("thumbnail extraction returned an unexpected file count")
        return info
