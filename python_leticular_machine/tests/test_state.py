import tempfile
import unittest
from pathlib import Path

from lenticular_machine.state import JobState


class JobStateTest(unittest.TestCase):
    def test_state_survives_reopening_database(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            database = Path(directory) / "state.sqlite3"
            JobState(database).set("job-1", "processing")
            self.assertEqual(JobState(database).get("job-1"), "processing")


if __name__ == "__main__":
    unittest.main()
