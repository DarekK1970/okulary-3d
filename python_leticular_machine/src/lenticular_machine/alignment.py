from __future__ import annotations

from dataclasses import dataclass
from pathlib import Path

import cv2
import numpy as np

from .processor import ProcessingError


@dataclass(frozen=True, slots=True)
class AlignmentConfig:
    z_center: float = 0.5
    z_width: float = 0.05
    alignment_y: float = 0.5
    y_window: float = 0.25
    maximum_shift_fraction: float = 0.15

    def validate(self) -> None:
        if not 0 <= self.z_center <= 1 or not 0.01 <= self.z_width <= 0.5:
            raise ProcessingError("Z region is outside the image")
        if not 0 <= self.alignment_y <= 1 or not 0.05 <= self.y_window <= 1:
            raise ProcessingError("vertical alignment region is outside the image")


@dataclass(frozen=True, slots=True)
class FrameTransform:
    filename: str
    x: float
    y: float
    score: float


@dataclass(frozen=True, slots=True)
class AlignmentResult:
    transforms: list[FrameTransform]
    crop: tuple[int, int, int, int]
    previews: list[Path]


class SequenceAligner:
    def align(self, sources: list[Path], output_dir: Path, config: AlignmentConfig) -> AlignmentResult:
        config.validate()
        if len(sources) < 2:
            raise ProcessingError("at least two frames are required for alignment")
        images = [cv2.imread(str(path), cv2.IMREAD_COLOR) for path in sources]
        if any(image is None for image in images):
            raise ProcessingError("one or more sequence frames cannot be decoded")
        height, width = images[0].shape[:2]
        if any(image.shape[:2] != (height, width) for image in images):
            raise ProcessingError("all sequence frames must have equal dimensions")

        x0 = max(0, round((config.z_center - config.z_width / 2) * width))
        x1 = min(width, round((config.z_center + config.z_width / 2) * width))
        y0 = max(0, round((config.alignment_y - config.y_window / 2) * height))
        y1 = min(height, round((config.alignment_y + config.y_window / 2) * height))
        reference = cv2.cvtColor(images[0][y0:y1, x0:x1], cv2.COLOR_BGR2GRAY).astype(np.float32)
        window = cv2.createHanningWindow((reference.shape[1], reference.shape[0]), cv2.CV_32F)
        transforms = [FrameTransform(sources[0].name, 0.0, 0.0, 1.0)]
        output_dir.mkdir(parents=True, exist_ok=True)
        cv2.imwrite(str(output_dir / sources[0].name), images[0])

        for source, image in zip(sources[1:], images[1:]):
            candidate = cv2.cvtColor(image[y0:y1, x0:x1], cv2.COLOR_BGR2GRAY).astype(np.float32)
            (observed_x, observed_y), score = cv2.phaseCorrelate(reference, candidate, window)
            shift_x, shift_y = -observed_x, -observed_y
            if abs(shift_x) > width * config.maximum_shift_fraction or abs(shift_y) > height * config.maximum_shift_fraction:
                raise ProcessingError(f"alignment shift is too large for {source.name}")
            matrix = np.float32([[1, 0, shift_x], [0, 1, shift_y]])
            aligned = cv2.warpAffine(image, matrix, (width, height), flags=cv2.INTER_LANCZOS4, borderMode=cv2.BORDER_CONSTANT)
            cv2.imwrite(str(output_dir / source.name), aligned, [cv2.IMWRITE_JPEG_QUALITY, 95])
            transforms.append(FrameTransform(source.name, shift_x, shift_y, float(score)))

        min_x = max(0, int(np.ceil(max(item.x for item in transforms))))
        max_x = min(width, int(np.floor(width + min(item.x for item in transforms))))
        min_y = max(0, int(np.ceil(max(item.y for item in transforms))))
        max_y = min(height, int(np.floor(height + min(item.y for item in transforms))))
        if max_x <= min_x or max_y <= min_y:
            raise ProcessingError("aligned frames have no common crop")
        preview_paths = []
        reference_image = cv2.imread(str(output_dir / sources[0].name), cv2.IMREAD_COLOR)
        comparison_indexes = sorted({len(sources) // 2, len(sources) - 1})
        for preview_index, source_index in enumerate(comparison_indexes):
            comparison = cv2.imread(str(output_dir / sources[source_index].name), cv2.IMREAD_COLOR)
            overlay = cv2.addWeighted(reference_image, 0.5, comparison, 0.5, 0)
            preview_path = output_dir / f"alignment_preview_{preview_index}.jpg"
            cv2.imwrite(str(preview_path), overlay, [cv2.IMWRITE_JPEG_QUALITY, 90])
            preview_paths.append(preview_path)

        return AlignmentResult(transforms, (min_x, min_y, max_x - min_x, max_y - min_y), preview_paths)
