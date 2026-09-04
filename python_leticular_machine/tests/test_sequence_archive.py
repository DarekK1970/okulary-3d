from __future__ import annotations

import io
import tarfile
import tempfile
import unittest
from pathlib import Path

import cv2
import numpy as np

from lenticular_machine.sequence import SequenceArchiveProcessor


class SequenceArchiveTest(unittest.TestCase):
    def test_imports_ordered_images_as_normalized_jpegs(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            archive_path = root / "sequence.tar"
            with tarfile.open(archive_path, "w") as archive:
                for name, value in [("frame_0002.png", 180), ("frame_0001.png", 40)]:
                    ok, encoded = cv2.imencode(".png", np.full((30, 50, 3), value, dtype=np.uint8))
                    self.assertTrue(ok)
                    info = tarfile.TarInfo(name)
                    info.size = len(encoded)
                    archive.addfile(info, io.BytesIO(encoded.tobytes()))

            output = root / "frames"
            info = SequenceArchiveProcessor().extract(archive_path, output)

            self.assertEqual((info.width, info.height, info.frame_count), (50, 30, 2))
            frames = sorted(output.glob("*.jpg"))
            self.assertEqual([item.name for item in frames], ["frame_0001.jpg", "frame_0002.jpg"])
            self.assertLess(float(cv2.imread(str(frames[0])).mean()), float(cv2.imread(str(frames[1])).mean()))


if __name__ == "__main__":
    unittest.main()
