from __future__ import annotations

from dataclasses import dataclass
from typing import Any


class ContractError(ValueError):
    pass


def _required(data: dict[str, Any], name: str, expected: type) -> Any:
    value = data.get(name)
    if not isinstance(value, expected):
        raise ContractError(f"{name} must be {expected.__name__}")
    return value


@dataclass(frozen=True, slots=True)
class RemoteFile:
    url: str
    sha256: str
    size_bytes: int
    filename: str

    @classmethod
    def from_dict(cls, data: dict[str, Any]) -> "RemoteFile":
        sha256 = _required(data, "sha256", str).lower()
        if len(sha256) != 64 or any(c not in "0123456789abcdef" for c in sha256):
            raise ContractError("sha256 must be a 64-character hexadecimal digest")
        size = _required(data, "size_bytes", int)
        if size <= 0:
            raise ContractError("size_bytes must be positive")
        return cls(
            url=_required(data, "url", str),
            sha256=sha256,
            size_bytes=size,
            filename=_required(data, "filename", str),
        )


@dataclass(frozen=True, slots=True)
class FrameSelection:
    start: int
    end: int
    step: int = 1
    jpeg_quality: int = 95

    @classmethod
    def from_dict(cls, data: dict[str, Any]) -> "FrameSelection":
        selection = cls(
            start=_required(data, "start", int),
            end=_required(data, "end", int),
            step=data.get("step", 1),
            jpeg_quality=data.get("jpeg_quality", 95),
        )
        if selection.start < 0 or selection.end < selection.start:
            raise ContractError("frame range is invalid")
        if selection.step < 1:
            raise ContractError("frame step must be positive")
        if not 1 <= selection.jpeg_quality <= 100:
            raise ContractError("jpeg_quality must be between 1 and 100")
        return selection


@dataclass(frozen=True, slots=True)
class JobManifest:
    job_id: str
    lease_token: str
    operation: str
    source: RemoteFile
    upload_url: str
    selection: FrameSelection | None
    artifact_kind: str

    @classmethod
    def from_dict(cls, data: dict[str, Any]) -> "JobManifest":
        operation = _required(data, "operation", str)
        if operation not in {"analyze_video", "extract_video_frames"}:
            raise ContractError(f"unsupported operation: {operation}")
        selection_data = data.get("selection")
        if operation == "extract_video_frames" and not isinstance(selection_data, dict):
            raise ContractError("selection is required for frame extraction")
        return cls(
            job_id=_required(data, "job_id", str),
            lease_token=_required(data, "lease_token", str),
            operation=operation,
            source=RemoteFile.from_dict(_required(data, "source", dict)),
            upload_url=_required(data, "upload_url", str),
            selection=FrameSelection.from_dict(selection_data) if isinstance(selection_data, dict) else None,
            artifact_kind=_required(data, "artifact_kind", str),
        )
