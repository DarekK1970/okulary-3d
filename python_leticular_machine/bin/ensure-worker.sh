#!/usr/bin/env sh
set -eu

PROJECT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
RUNTIME_DIR="$PROJECT_DIR/runtime"
PID_FILE="$RUNTIME_DIR/worker.pid"
mkdir -p "$RUNTIME_DIR"

exec 9>"$RUNTIME_DIR/watchdog.lock"
flock -n 9 || exit 0

if [ -s "$PID_FILE" ] && kill -0 "$(cat "$PID_FILE")" 2>/dev/null; then
    exit 0
fi

nohup "$PROJECT_DIR/bin/run-worker.sh" >> "$RUNTIME_DIR/worker.log" 2>&1 &
echo "$!" > "$PID_FILE"
