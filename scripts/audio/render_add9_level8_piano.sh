#!/bin/bash
# Renders Add 9 · Level 8's Piano audio from gen_add9_level8_voicings.php's
# output (192 drop-2, rotated-position chords across 12 keys).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
OUT="$(cd "$SCRIPT_DIR/../.." && pwd)/storage/app/public/add9/level8/piano"
mkdir -p "$OUT"
VELOCITY=100
BEATS=3.2

count=0
while IFS=$'\t' read -r slug notes; do
  IFS=',' read -ra note_arr <<< "$notes"
  "$SCRIPT_DIR/render_note.sh" "$OUT/${slug}.wav" 0 $VELOCITY $BEATS "${note_arr[@]}"
  count=$((count + 1))
done < <(php "$SCRIPT_DIR/gen_add9_level8_voicings.php")

echo "Rendered ${count} files."
