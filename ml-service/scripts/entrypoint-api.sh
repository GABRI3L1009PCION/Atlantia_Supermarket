#!/usr/bin/env sh
set -eu

python -m py_compile app/main.py

exec uvicorn app.main:create_app --factory --host 0.0.0.0 --port 8000
