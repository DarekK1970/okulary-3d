from __future__ import annotations

import tarfile
from pathlib import Path, PurePosixPath

import cv2
import numpy as np

from .processor import ProcessingError, VideoInfo


class SequenceArchiveProcessor:
    ALLOWED_SUFFIXES = {".jpg", ".jpeg", ".png", ".webp"}

    def extract(self, source: Path, output_dir: Path) -> VideoInfo:
        output_dir.mkdir(parents=True, exist_ok=True)
        try:
            with tarfile.open(source, "r:*") as archive:
                members = [member for member in archive.getmembers() if member.isfile()]
                if not 2 <= len(members) <= 100:
                    raise ProcessingError("photo sequence must contain 2 to 100 images")
                dimensions: tuple[int, int] | None = None
                for index, member in enumerate(sorted(members, key=lambda item: item.name)):
                    name = PurePosixPath(member.name)
                    if len(name.parts) != 1 or name.suffix.lower() not in self.ALLOWED_SUFFIXES:
                        raise ProcessingError("sequence archive contains an invalid entry")
                    stream = archive.extractfile(member)
                    if stream is None:
                        raise ProcessingError("sequence image could not be read")
                    encoded = stream.read()
                    image = cv2.imdecode(np.frombuffer(encoded, dtype="uint8"), cv2.IMREAD_COLOR)
                    if image is None:
                        raise ProcessingError("sequence image could not be decoded")
                    current = (image.shape[1], image.shape[0])
                    dimensions = dimensions or current
                    if current != dimensions:
                        raise ProcessingError("all sequence images must have equal dimensions")
                    if not cv2.imwrite(str(output_dir / f"frame_{index + 1:04d}.jpg"), image, [cv2.IMWRITE_JPEG_QUALITY, 95]):
                        raise ProcessingError("sequence image could not be normalized")
        except (tarfile.TarError, OSError) as exc:
            raise ProcessingError("invalid sequence archive") from exc

        assert dimensions is not None
        return VideoInfo(width=dimensions[0], height=dimensions[1], frame_count=len(members), fps=1.0, duration_seconds=float(len(members)))
