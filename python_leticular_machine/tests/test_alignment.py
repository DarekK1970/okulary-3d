import tempfile
import unittest
from pathlib import Path

import cv2
import numpy as np

from lenticular_machine.alignment import AlignmentConfig, SequenceAligner


class AlignmentTest(unittest.TestCase):
    def test_recovers_known_translation_in_z_region(self) -> None:
        random = np.random.default_rng(42)
        reference = random.integers(0, 256, (240, 320, 3), dtype=np.uint8)
        shifted = cv2.warpAffine(reference, np.float32([[1, 0, 7], [0, 1, -4]]), (320, 240))

        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            first, second = root / "first.jpg", root / "second.jpg"
            cv2.imwrite(str(first), reference, [cv2.IMWRITE_JPEG_QUALITY, 100])
            cv2.imwrite(str(second), shifted, [cv2.IMWRITE_JPEG_QUALITY, 100])

            result = SequenceAligner().align([first, second], root / "aligned", AlignmentConfig(z_width=0.2, y_window=0.5))

            self.assertAlmostEqual(result.transforms[1].x, -7, delta=0.5)
            self.assertAlmostEqual(result.transforms[1].y, 4, delta=0.5)
            self.assertGreater(result.transforms[1].score, 0.5)
            self.assertEqual(result.crop, (0, 4, 312, 236))
            self.assertEqual(len(result.previews), 1)
            self.assertTrue(result.previews[0].exists())


if __name__ == "__main__":
    unittest.main()
