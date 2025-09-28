#!/usr/bin/env bash
set -euo pipefail

REQUIRED=( ".editorconfig" ".gitattributes" ".gitignore" "LICENSE" "README.md" "CODEOWNERS" )
for f in "${REQUIRED[@]}"; do
  [[ -f "$f" ]] || { echo "ERROR: falta $f"; exit 1; }
done

# Fin de línea y newline final en archivos clave
for f in "${REQUIRED[@]}"; do
  tail -c1 "$f" | od -An -t x1 | grep -q "0a" || { echo "ERROR: $f sin newline final"; exit 1; }
done

echo "OK repo: archivos base presentes y saneados."
