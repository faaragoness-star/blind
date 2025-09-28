#!/usr/bin/env bash
set -euo pipefail

# Reglas mínimas: cada plugin debe tener README. Si existe plugin.php, validar Version SemVer.
shopt -s nullglob
for dir in plugins/*/ ; do
  name=$(basename "$dir")
  [[ -f "${dir}/README.md" ]] || { echo "ERROR: ${name} sin README.md"; exit 1; }
  if [[ -f "${dir}/plugin.php" ]]; then
    ver=$(grep -Eoi '^[ \t\/*#]*Version:\s*([0-9]+\.[0-9]+\.[0-9]+)' "${dir}/plugin.php" || true)
    [[ -n "$ver" ]] || { echo "ERROR: ${name}/plugin.php sin Version SemVer"; exit 1; }
  fi
  echo "OK plugin: ${name}"
done
echo "OK plugins: verificación mínima correcta."
