#!/usr/bin/env bash
set -euo pipefail

ASSET_DIR="assets/models"
MAX=$((12*1024*1024)) # 12 MB

[[ -d "$ASSET_DIR" ]] || { echo "OK assets: no hay $ASSET_DIR (saltado)"; exit 0; }

python3 scripts/verify_glb.py --dir "$ASSET_DIR" --max-bytes "$MAX"
