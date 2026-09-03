from __future__ import annotations

import hashlib
import http.client
import json
import urllib.error
import urllib.request
from pathlib import Path
from typing import Any
from urllib.parse import urlparse

from .config import Settings
from .models import JobManifest
from .security import signed_headers, validate_remote_url


class GatewayError(RuntimeError):
    pass


class PortalGateway:
    def __init__(self, settings: Settings) -> None:
        self.settings = settings

    def _json(self, method: str, path: str, payload: dict[str, Any]) -> dict[str, Any] | None:
        body = json.dumps(payload, separators=(",", ":")).encode()
        headers = signed_headers(
            key_id=self.settings.api_key_id,
            secret=self.settings.api_secret,
            machine_id=self.settings.machine_id,
            method=method,
            path=path,
            body=body,
        )
        request = urllib.request.Request(
            f"{self.settings.portal_url}{path}", data=body, headers=headers, method=method
        )
        try:
            with urllib.request.urlopen(request, timeout=30) as response:
                raw = response.read()
        except urllib.error.HTTPError as exc:
            if exc.code == 204:
                return None
            raise GatewayError(f"portal returned HTTP {exc.code}") from exc
        except urllib.error.URLError as exc:
            raise GatewayError(f"portal is unavailable: {exc.reason}") from exc
        return json.loads(raw) if raw else None

    def claim(self) -> JobManifest | None:
        data = self._json("POST", "/api/worker/v1/jobs/claim", {
            "lease_seconds": self.settings.lease_seconds,
            "capabilities": ["analyze_video:v1", "extract_video_frames:v1"],
        })
        return JobManifest.from_dict(data) if data else None

    def progress(self, job: JobManifest, percent: int, stage: str) -> None:
        self._json("POST", f"/api/worker/v1/jobs/{job.job_id}/progress", {
            "lease_token": job.lease_token, "percent": percent, "stage": stage,
        })

    def heartbeat(self, job: JobManifest) -> None:
        self._json("POST", f"/api/worker/v1/jobs/{job.job_id}/heartbeat", {
            "lease_token": job.lease_token, "lease_seconds": self.settings.lease_seconds,
        })

    def complete(self, job: JobManifest, sha256: str, size_bytes: int, result: dict[str, Any] | None = None) -> None:
        payload: dict[str, Any] = {
            "lease_token": job.lease_token,
            "artifact": {"sha256": sha256, "size_bytes": size_bytes, "media_type": "application/zip"},
        }
        if result is not None:
            payload["result"] = result
        self._json("POST", f"/api/worker/v1/jobs/{job.job_id}/complete", payload)

    def fail(self, job: JobManifest, code: str, message: str) -> None:
        self._json("POST", f"/api/worker/v1/jobs/{job.job_id}/fail", {
            "lease_token": job.lease_token, "error": {"code": code, "message": message[:1000]},
        })

    def download(self, job: JobManifest, destination: Path) -> None:
        validate_remote_url(job.source.url, self.settings.allowed_download_hosts)
        request = urllib.request.Request(job.source.url, method="GET")
        digest = hashlib.sha256()
        total = 0
        with urllib.request.urlopen(request, timeout=120) as response, destination.open("wb") as output:
            validate_remote_url(response.geturl(), self.settings.allowed_download_hosts)
            while chunk := response.read(1024 * 1024):
                total += len(chunk)
                if total > job.source.size_bytes:
                    raise GatewayError("download exceeded declared size")
                digest.update(chunk)
                output.write(chunk)
        if total != job.source.size_bytes or digest.hexdigest() != job.source.sha256:
            destination.unlink(missing_ok=True)
            raise GatewayError("download integrity check failed")

    def upload(self, job: JobManifest, artifact: Path) -> None:
        validate_remote_url(job.upload_url, self.settings.allowed_download_hosts)
        parsed = urlparse(job.upload_url)
        path = parsed.path + (("?" + parsed.query) if parsed.query else "")
        connection = http.client.HTTPSConnection(parsed.hostname, parsed.port or 443, timeout=300)
        try:
            connection.putrequest("PUT", path)
            connection.putheader("Content-Type", "application/zip")
            connection.putheader("Content-Length", str(artifact.stat().st_size))
            connection.endheaders()
            with artifact.open("rb") as stream:
                while chunk := stream.read(1024 * 1024):
                    connection.send(chunk)
            response = connection.getresponse()
            response.read()
            if response.status not in (200, 201, 204):
                raise GatewayError(f"upload returned HTTP {response.status}")
        finally:
            connection.close()
