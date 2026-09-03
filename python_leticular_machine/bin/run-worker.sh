#!/usr/bin/env sh
set -eu

PROJECT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
cd "$PROJECT_DIR"

set -a
. "$PROJECT_DIR/.env"
set +a

exec env PYTHONPATH="$PROJECT_DIR/src" python3 -c 'from lenticular_machine.cli import worker_main; worker_main()'
