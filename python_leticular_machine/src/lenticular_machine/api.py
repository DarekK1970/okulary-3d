from __future__ import annotations

import shutil

from fastapi import FastAPI, Response, status

from . import __version__
from .config import ConfigurationError, Settings

app = FastAPI(title="Lenticular Machine", version=__version__, docs_url=None, redoc_url=None)


@app.get("/health/live")
def live() -> dict[str, str]:
    return {"status": "ok", "version": __version__}


@app.get("/health/ready")
def ready(response: Response) -> dict[str, object]:
    try:
        settings = Settings.from_env()
        settings.runtime_dir.mkdir(parents=True, exist_ok=True)
        missing = [binary for binary in (settings.ffmpeg, settings.ffprobe) if not shutil.which(binary)]
        if missing:
            response.status_code = status.HTTP_503_SERVICE_UNAVAILABLE
            return {"status": "not_ready", "missing": missing}
        return {"status": "ready", "machine_id": settings.machine_id}
    except ConfigurationError as exc:
        response.status_code = status.HTTP_503_SERVICE_UNAVAILABLE
        return {"status": "not_ready", "error": str(exc)}
