#!/usr/bin/env bash
set -euo pipefail
shopt -s nullglob

fail(){ echo "ERROR: $1"; exit 1; }

for dir in plugins/*/ ; do
  slug="$(basename "$dir")"
  main="${dir}plugin.php"
  [[ -f "$main" ]] || fail "${slug}: falta plugin.php"
  [[ -f "${dir}readme.txt" ]] || fail "${slug}: falta readme.txt"
  [[ -d "${dir}languages" ]] || fail "${slug}: falta carpeta languages/"
  [[ -f "${dir}languages/.keep" ]] || fail "${slug}: falta languages/.keep"

  # Cabecera WP
  name=$(grep -Eoi '^\s*/\*\*|^\s*\*' "$main" -n | sed -n '1,20p' >/dev/null; grep -Eoi '^\s*\*\s*Plugin Name:\s*(.+)$' "$main" | head -1 | sed -E 's/.*:\s*//I')
  ver=$(grep -Eoi '^\s*\*\s*Version:\s*([0-9]+\.[0-9]+\.[0-9]+([.-][0-9A-Za-z]+)?)' "$main" | sed -E 's/.*:\s*//I')
  reqwp=$(grep -Eoi '^\s*\*\s*Requires at least:\s*([0-9]+(\.[0-9]+)*)' "$main" | sed -E 's/.*:\s*//I')
  reqphp=$(grep -Eoi '^\s*\*\s*Requires PHP:\s*([0-9]+(\.[0-9]+)*)' "$main" | sed -E 's/.*:\s*//I')
  tdom=$(grep -Eoi '^\s*\*\s*Text Domain:\s*([a-z0-9-]+)' "$main" | sed -E 's/.*:\s*//I')
  lic=$(grep -Eoi '^\s*\*\s*License:\s*(.+)$' "$main" | sed -E 's/.*:\s*//I')

  [[ -n "$name" ]]   || fail "${slug}: Plugin Name vacío"
  [[ -n "$ver" ]]    || fail "${slug}: Version vacía"
  [[ "$ver" =~ ^[0-9]+\.[0-9]+\.[0-9]+([.-][0-9A-Za-z]+)?$ ]] || fail "${slug}: Version no es SemVer (${ver})"
  [[ -n "$reqwp" ]]  || fail "${slug}: Requires at least vacío"
  [[ -n "$reqphp" ]] || fail "${slug}: Requires PHP vacío"
  [[ -n "$tdom" ]]   || fail "${slug}: Text Domain vacío"
  [[ "$tdom" == "$slug" ]] || fail "${slug}: Text Domain (${tdom}) debe ser igual al slug (${slug})"
  [[ "$lic" =~ MIT|GPL|BSD ]] || fail "${slug}: License no válida (${lic})"

  echo "OK ${slug}: ${name} v${ver} (TD: ${tdom})"

done
echo "OK plugins: validación estricta."
