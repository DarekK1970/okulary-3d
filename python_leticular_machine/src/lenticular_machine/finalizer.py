from __future__ import annotations

import re
from dataclasses import dataclass
from pathlib import Path

import cv2

from .alignment import AlignmentResult
from .processor import ProcessingError


@dataclass(frozen=True, slots=True)
class FinalizationResult:
    frame_count: int
    width: int
    height: int
    previews: list[Path]


class SequenceFinalizer:
    def finalize(
        self,
        aligned_dir: Path,
        alignment: AlignmentResult,
        output_dir: Path,
        preview_dir: Path,
        crop: dict[str, float],
        basename: str,
        reverse: bool,
    ) -> FinalizationResult:
        sources = [aligned_dir / transform.filename for transform in alignment.transforms]
        if reverse:
            sources.reverse()
        image = cv2.imread(str(sources[0]), cv2.IMREAD_COLOR)
        height, width = image.shape[:2]
        x = round(float(crop["x"]) * width)
        y = round(float(crop["y"]) * height)
        crop_width = round(float(crop["width"]) * width)
        crop_height = round(float(crop["height"]) * height)
        common_x, common_y, common_width, common_height = alignment.crop
        x = max(x, common_x)
        y = max(y, common_y)
        right = min(x + crop_width, common_x + common_width, width)
        bottom = min(y + crop_height, common_y + common_height, height)
        if right - x < 2 or bottom - y < 2:
            raise ProcessingError("selected crop is outside the common aligned area")

        safe_name = re.sub(r"[^a-zA-Z0-9_-]+", "_", basename).strip("_") or "lenticular"
        output_dir.mkdir(parents=True, exist_ok=True)
        preview_dir.mkdir(parents=True, exist_ok=True)
        previews = []
        for index, source in enumerate(sources, start=1):
            frame = cv2.imread(str(source), cv2.IMREAD_COLOR)
            cropped = frame[y:bottom, x:right]
            output = output_dir / f"{safe_name}_{index:03d}.jpg"
            cv2.imwrite(str(output), cropped, [cv2.IMWRITE_JPEG_QUALITY, 95])
            preview = cropped
            scale = min(1.0, 720 / cropped.shape[1])
            if scale < 1:
                preview = cv2.resize(cropped, None, fx=scale, fy=scale, interpolation=cv2.INTER_AREA)
            preview_path = preview_dir / f"preview_{index:03d}.jpg"
            cv2.imwrite(str(preview_path), preview, [cv2.IMWRITE_JPEG_QUALITY, 82])
            previews.append(preview_path)

        return FinalizationResult(len(sources), right - x, bottom - y, previews)
