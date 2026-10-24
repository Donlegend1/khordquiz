#!/bin/bash
# Re-renders Celesta for Basic Triads Levels 1, 2, 5, 6 (the close-position
# levels — root was 48) one octave higher, base 60 — confirmed by ear.
# Levels 3, 4, 7, 8 (drop-2) are untouched since they already render at
# base 60, the same register the user just confirmed sounds best. Same
# file paths — overwrites in place, no DB changes needed.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
STORAGE="$(cd "$SCRIPT_DIR/../.." && pwd)/storage/app/public/basic-triads"
PROGRAM=8
VELOCITY=100
BEATS=3.2
NEW_BASE=60

render_level() {
  local level="$1" outdir="$2"
  mkdir -p "$outdir"
  while IFS=$'\t' read -r slug notes; do
    IFS=',' read -ra na <<< "$notes"
    "$SCRIPT_DIR/render_note.sh" "$outdir/${slug}.wav" $PROGRAM $VELOCITY $BEATS "${na[@]}"
  done < <(php "$SCRIPT_DIR/gen_basic_triads_new_instruments_voicings.php" "$level" "$NEW_BASE")
}

render_level 1 "$STORAGE/celesta"
echo "done: level1"
render_level 2 "$STORAGE/level2/celesta"
echo "done: level2"
render_level 5 "$STORAGE/level5/celesta"
echo "done: level5"
render_level 6 "$STORAGE/level6/celesta"
echo "done: level6"

echo "Done."
