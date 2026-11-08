#!/bin/bash
# Re-renders Organ for 7th Degree Chords Levels 1, 2, 5, 6 (the
# CLOSE-POSITION levels — unlike Add 9, this category's drop-2 levels are 3,
# 4, 7, 8, not 5-8) one octave higher — base 48 (C3) -> 60 (C4). Same
# diagnosis/fix as fix_add9_organ_octave.sh: a dense chord sitting at the
# bottom of Organ's drawbar range produces audible beating/mud; moving it up
# an octave (matching this category's own drop-2 levels, which were never
# moved and are already at C4) fixes it. Same file paths — overwrites in
# place, no DB changes needed.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
STORAGE="$(cd "$SCRIPT_DIR/../.." && pwd)/storage/app/public/seventh-degree-chords"
PROGRAM=16
VELOCITY=100
BEATS=3.2
NEW_BASE=60

render_level() {
  local level="$1" outdir="$2"
  mkdir -p "$outdir"
  while IFS=$'\t' read -r slug notes; do
    IFS=',' read -ra na <<< "$notes"
    "$SCRIPT_DIR/render_note.sh" "$outdir/${slug}.wav" $PROGRAM $VELOCITY $BEATS "${na[@]}"
  done < <(php "$SCRIPT_DIR/gen_seventh_voicings.php" "$level" "$NEW_BASE")
}

render_level 1 "$STORAGE/level1/organ"
echo "done: level1"
render_level 2 "$STORAGE/level2/organ"
echo "done: level2"
render_level 5 "$STORAGE/level5/organ"
echo "done: level5"
render_level 6 "$STORAGE/level6/organ"
echo "done: level6"

echo "Done."
