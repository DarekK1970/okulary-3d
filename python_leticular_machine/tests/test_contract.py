import hashlib
import unittest

from lenticular_machine.models import ContractError, JobManifest
from lenticular_machine.security import signed_headers, validate_remote_url


def manifest() -> dict:
    return {
        "job_id": "job-1",
        "lease_token": "lease-1",
        "operation": "extract_video_frames",
        "source": {
            "url": "https://okulary-3d.pl/private/source.mp4",
            "sha256": "a" * 64,
            "size_bytes": 100,
            "filename": "source.mp4",
        },
        "upload_url": "https://okulary-3d.pl/private/result",
        "artifact_kind": "frames",
        "selection": {"start": 10, "end": 20, "step": 2, "jpeg_quality": 95},
    }


class ContractTest(unittest.TestCase):
    def test_parses_supported_job(self) -> None:
        job = JobManifest.from_dict(manifest())
        self.assertEqual(job.selection.start, 10)
        self.assertEqual(job.selection.end, 20)

    def test_rejects_invalid_range(self) -> None:
        data = manifest()
        data["selection"]["end"] = 9
        with self.assertRaises(ContractError):
            JobManifest.from_dict(data)

    def test_rejects_invalid_jpeg_quality(self) -> None:
        data = manifest()
        data["selection"]["jpeg_quality"] = 101
        with self.assertRaises(ContractError):
            JobManifest.from_dict(data)

    def test_analysis_does_not_require_frame_selection(self) -> None:
        data = manifest()
        data["operation"] = "analyze_video"
        data["artifact_kind"] = "analysis"
        del data["selection"]

        job = JobManifest.from_dict(data)

        self.assertIsNone(job.selection)

    def test_parses_alignment_job_options(self) -> None:
        data = manifest()
        data["operation"] = "align_sequence"
        data["artifact_kind"] = "aligned"
        data["alignment"] = {"z_center": 0.4, "z_width": 0.05, "alignment_y": 0.6}

        job = JobManifest.from_dict(data)

        self.assertEqual(job.alignment["z_center"], 0.4)

    def test_rejects_untrusted_download_host(self) -> None:
        with self.assertRaises(ValueError):
            validate_remote_url("https://attacker.example/file", frozenset({"okulary-3d.pl"}))

    def test_signature_is_deterministic_for_nonce_and_timestamp(self) -> None:
        headers = signed_headers(
            key_id="key", secret="s" * 32, machine_id="machine", method="POST",
            path="/api/jobs", body=b"{}", timestamp=1_700_000_000, nonce="abc",
        )
        canonical = "POST\n/api/jobs\n1700000000\nabc\n" + hashlib.sha256(b"{}").hexdigest()
        expected = __import__("hmac").new(b"s" * 32, canonical.encode(), hashlib.sha256).hexdigest()
        self.assertEqual(headers["X-Signature"], expected)


if __name__ == "__main__":
    unittest.main()
