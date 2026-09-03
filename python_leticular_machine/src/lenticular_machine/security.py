from __future__ import annotations

import hashlib
import hmac
import time
import uuid
from urllib.parse import urlparse


def signed_headers(
    *, key_id: str, secret: str, machine_id: str, method: str, path: str, body: bytes,
    timestamp: int | None = None, nonce: str | None = None,
) -> dict[str, str]:
    timestamp = timestamp or int(time.time())
    nonce = nonce or uuid.uuid4().hex
    digest = hashlib.sha256(body).hexdigest()
    canonical = "\n".join((method.upper(), path, str(timestamp), nonce, digest))
    signature = hmac.new(secret.encode(), canonical.encode(), hashlib.sha256).hexdigest()
    return {
        "Content-Type": "application/json",
        "X-Machine-Id": machine_id,
        "X-Api-Key-Id": key_id,
        "X-Timestamp": str(timestamp),
        "X-Nonce": nonce,
        "X-Content-SHA256": digest,
        "X-Signature": signature,
    }


def validate_remote_url(url: str, allowed_hosts: frozenset[str]) -> None:
    parsed = urlparse(url)
    if parsed.scheme != "https" or not parsed.hostname:
        raise ValueError("remote file URL must use HTTPS")
    if parsed.username or parsed.password:
        raise ValueError("credentials are forbidden in remote file URLs")
    if parsed.hostname.lower() not in allowed_hosts:
        raise ValueError(f"remote file host is not allowed: {parsed.hostname}")

