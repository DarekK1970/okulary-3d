from __future__ import annotations

import sqlite3
from contextlib import closing
from datetime import datetime, timezone
from pathlib import Path


class JobState:
    def __init__(self, database: Path) -> None:
        database.parent.mkdir(parents=True, exist_ok=True)
        self.database = database
        with closing(self._connect()) as connection:
            connection.execute(
                """CREATE TABLE IF NOT EXISTS jobs (
                    job_id TEXT PRIMARY KEY,
                    state TEXT NOT NULL,
                    detail TEXT,
                    updated_at TEXT NOT NULL
                )"""
            )
            connection.commit()

    def _connect(self) -> sqlite3.Connection:
        return sqlite3.connect(self.database, timeout=30)

    def set(self, job_id: str, state: str, detail: str | None = None) -> None:
        now = datetime.now(timezone.utc).isoformat()
        with closing(self._connect()) as connection:
            connection.execute(
                """INSERT INTO jobs(job_id, state, detail, updated_at) VALUES (?, ?, ?, ?)
                ON CONFLICT(job_id) DO UPDATE SET state=excluded.state,
                detail=excluded.detail, updated_at=excluded.updated_at""",
                (job_id, state, detail, now),
            )
            connection.commit()

    def get(self, job_id: str) -> str | None:
        with closing(self._connect()) as connection:
            row = connection.execute("SELECT state FROM jobs WHERE job_id = ?", (job_id,)).fetchone()
        return row[0] if row else None
