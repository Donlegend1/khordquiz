#!/bin/bash
# Re-renders Organ for Add 9 Levels 1-4 (close-position) one octave higher —
# base 48 (C3) -> 60 (C4). Confirmed by ear: the add9 voicing's tight root-
# to-9th whole-step clash sits right at the bottom of Organ's range at C3,
# producing audible beating/mud through its harmonically-rich drawbar
# timbre; the same voicing an octave up (or in the drop-2 levels, which
# were already at C4 and never moved) sounds clean. Levels 5-8 (drop-2)
# are untouched — already at base 60, already confirmed clean.
# Same file paths as before — this overwrites in place, no DB changes needed.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
STORAGE="$(cd "$SCRIPT_DIR/../.." && pwd)/storage/app/public/add9"
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
  done < <(php "$SCRIPT_DIR/gen_add9_voicings.php" "$level" "$NEW_BASE")
}

render_level 1 "$STORAGE/organ"
echo "done: level1"
render_level 2 "$STORAGE/level2/organ"
echo "done: level2"
render_level 3 "$STORAGE/level3/organ"
echo "done: level3"
render_level 4 "$STORAGE/level4/organ"
echo "done: level4"

echo "Done."
