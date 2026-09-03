from __future__ import annotations

import json
import os
import shutil
import subprocess
import tempfile
import unittest
from pathlib import Path

from lenticular_machine.models import JobManifest
from lenticular_machine.processor import VideoFrameProcessor


PROJECT_DIR = Path(__file__).resolve().parents[1]
FIXTURE = PROJECT_DIR / "media" / "lenticular_test.mp4"


def find_binary(name: str) -> str | None:
    configured_dir = os.getenv("LENTICULAR_FFMPEG_BIN")
    if configured_dir:
        candidate = Path(configured_dir) / f"{name}.exe"
        if candidate.is_file():
            return str(candidate)

    discovered = shutil.which(name)
    if discovered:
        return discovered

    if os.name == "nt":
        programs = Path(os.getenv("LOCALAPPDATA", "")) / "Programs" / "ffmpeg"
        matches = sorted(programs.glob(f"**/{name}.exe"))
        if matches:
            return str(matches[-1])

    return None


FFMPEG = find_binary("ffmpeg")
FFPROBE = find_binary("ffprobe")


@unittest.skipUnless(FFMPEG and FFPROBE and FIXTURE.is_file(), "FFmpeg or video fixture unavailable")
class VideoIntegrationTest(unittest.TestCase):
    def test_extracts_selected_real_video_frames_as_jpeg(self) -> None:
        manifest = JobManifest.from_dict({
            "job_id": "integration-video",
            "lease_token": "local-test",
            "operation": "extract_video_frames",
            "source": {
                "url": "https://okulary-3d.pl/test/lenticular_test.mp4",
                "sha256": "0" * 64,
                "size_bytes": FIXTURE.stat().st_size,
                "filename": FIXTURE.name,
            },
            "upload_url": "https://okulary-3d.pl/test/result.zip",
            "artifact_kind": "frames",
            "selection": {"start": 0, "end": 12, "step": 3, "jpeg_quality": 95},
        })
        processor = VideoFrameProcessor(ffmpeg=FFMPEG, ffprobe=FFPROBE)

        with tempfile.TemporaryDirectory() as directory:
            output_dir = Path(directory) / "frames"
            video = processor.extract(manifest, FIXTURE, output_dir)
            frames = sorted(output_dir.glob("frame_*.jpg"))

            self.assertEqual((video.width, video.height, video.frame_count), (1178, 786, 97))
            self.assertEqual([frame.name for frame in frames], [
                "frame_000001.jpg",
                "frame_000002.jpg",
                "frame_000003.jpg",
                "frame_000004.jpg",
                "frame_000005.jpg",
            ])
            self.assertTrue(all(frame.read_bytes().startswith(b"\xff\xd8\xff") for frame in frames))

            result = subprocess.run(
                [FFPROBE, "-v", "error", "-select_streams", "v:0", "-show_entries",
                 "stream=codec_name,width,height", "-of", "json", str(frames[0])],
                capture_output=True,
                text=True,
                check=True,
                timeout=30,
            )
            stream = json.loads(result.stdout)["streams"][0]
            self.assertEqual(stream, {"codec_name": "mjpeg", "width": 1178, "height": 786})

    def test_analyzes_video_and_generates_three_thumbnails(self) -> None:
        processor = VideoFrameProcessor(ffmpeg=FFMPEG, ffprobe=FFPROBE)

        with tempfile.TemporaryDirectory() as directory:
            output_dir = Path(directory) / "analysis"
            video = processor.analyze(FIXTURE, output_dir)

            self.assertEqual((video.width, video.height, video.frame_count), (1178, 786, 97))
            self.assertEqual(len(list(output_dir.glob("thumbnail_*.jpg"))), 3)


if __name__ == "__main__":
    unittest.main()
