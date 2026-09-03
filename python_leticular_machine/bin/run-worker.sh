#!/usr/bin/env sh
set -eu

PROJECT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
cd "$PROJECT_DIR"

set -a
. "$PROJECT_DIR/.env"
set +a

PYTHON_BIN="$PROJECT_DIR/.venv/bin/python"
if [ ! -x "$PYTHON_BIN" ]; then
    PYTHON_BIN=python3
fi

exec env PYTHONPATH="$PROJECT_DIR/src" "$PYTHON_BIN" -c 'from lenticular_machine.cli import worker_main; worker_main()'
