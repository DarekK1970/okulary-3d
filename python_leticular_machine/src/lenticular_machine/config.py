from __future__ import annotations

import os
from dataclasses import dataclass
from pathlib import Path
from urllib.parse import urlparse


class ConfigurationError(ValueError):
    pass


@dataclass(frozen=True, slots=True)
class Settings:
    portal_url: str
    machine_id: str
    api_key_id: str
    api_secret: str
    allowed_download_hosts: frozenset[str]
    runtime_dir: Path
    poll_seconds: float = 5.0
    lease_seconds: int = 120
    ffmpeg: str = "ffmpeg"
    ffprobe: str = "ffprobe"

    @classmethod
    def from_env(cls) -> "Settings":
        portal_url = os.getenv("LENTICULAR_PORTAL_URL", "").rstrip("/")
        secret = os.getenv("LENTICULAR_API_SECRET", "")
        hosts = frozenset(
            host.strip().lower()
            for host in os.getenv("LENTICULAR_ALLOWED_DOWNLOAD_HOSTS", "").split(",")
            if host.strip()
        )
        settings = cls(
            portal_url=portal_url,
            machine_id=os.getenv("LENTICULAR_MACHINE_ID", ""),
            api_key_id=os.getenv("LENTICULAR_API_KEY_ID", ""),
            api_secret=secret,
            allowed_download_hosts=hosts,
            runtime_dir=Path(os.getenv("LENTICULAR_RUNTIME_DIR", "runtime")).resolve(),
            poll_seconds=float(os.getenv("LENTICULAR_POLL_SECONDS", "5")),
            lease_seconds=int(os.getenv("LENTICULAR_LEASE_SECONDS", "120")),
            ffmpeg=os.getenv("LENTICULAR_FFMPEG", "ffmpeg"),
            ffprobe=os.getenv("LENTICULAR_FFPROBE", "ffprobe"),
        )
        settings.validate()
        return settings

    def validate(self) -> None:
        parsed = urlparse(self.portal_url)
        errors: list[str] = []
        if parsed.scheme != "https" or not parsed.hostname:
            errors.append("LENTICULAR_PORTAL_URL must be an HTTPS URL")
        if not self.machine_id:
            errors.append("LENTICULAR_MACHINE_ID is required")
        if not self.api_key_id:
            errors.append("LENTICULAR_API_KEY_ID is required")
        if len(self.api_secret) < 32:
            errors.append("LENTICULAR_API_SECRET must contain at least 32 characters")
        if not self.allowed_download_hosts:
            errors.append("LENTICULAR_ALLOWED_DOWNLOAD_HOSTS is required")
        if self.lease_seconds < 30:
            errors.append("LENTICULAR_LEASE_SECONDS must be at least 30")
        if errors:
            raise ConfigurationError("; ".join(errors))

