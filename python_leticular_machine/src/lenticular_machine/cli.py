from __future__ import annotations

import logging

from .config import Settings
from .worker import Worker


def worker_main() -> None:
    logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(name)s %(message)s")
    Worker(Settings.from_env()).run()


def api_main() -> None:
    import uvicorn

    uvicorn.run("lenticular_machine.api:app", host="127.0.0.1", port=8081)

