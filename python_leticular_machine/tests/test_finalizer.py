import tempfile
import unittest
from pathlib import Path

import cv2
import numpy as np

from lenticular_machine.alignment import AlignmentResult, FrameTransform
from lenticular_machine.finalizer import SequenceFinalizer


class FinalizerTest(unittest.TestCase):
    def test_crops_renames_and_reverses_aligned_sequence(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            aligned = root / "aligned"
            aligned.mkdir()
            names = ["frame_001.jpg", "frame_002.jpg"]
            cv2.imwrite(str(aligned / names[0]), np.full((100, 200, 3), 20, dtype=np.uint8))
            cv2.imwrite(str(aligned / names[1]), np.full((100, 200, 3), 220, dtype=np.uint8))
            alignment = AlignmentResult(
                [FrameTransform(names[0], 0, 0, 1), FrameTransform(names[1], 0, 0, 1)],
                (10, 5, 180, 90), [], [],
            )

            result = SequenceFinalizer().finalize(
                aligned, alignment, root / "output", root / "previews",
                {"x": 0.25, "y": 0.2, "width": 0.5, "height": 0.6}, "Mój projekt", True,
            )

            outputs = sorted((root / "output").glob("*.jpg"))
            self.assertEqual([path.name for path in outputs], ["M_j_projekt_001.jpg", "M_j_projekt_002.jpg"])
            self.assertEqual((result.width, result.height), (100, 60))
            self.assertEqual(result.frame_count, 2)
            self.assertGreater(float(cv2.imread(str(outputs[0])).mean()), 200)
            self.assertEqual(len(result.previews), 2)


if __name__ == "__main__":
    unittest.main()
