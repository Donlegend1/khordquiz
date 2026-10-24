#!/bin/bash
# Shifts every existing Bass Guitar audio file in Basic Triads (all 8 levels)
# up by exactly one octave (+1200 cents) in place, using sox's `pitch` effect.
# Done as a pitch-shift of the existing renders (not a re-synthesis from MIDI)
# because no saved manifest of the original chord voicings survives for these
# levels — this guarantees the exact same voicing shape, just transposed.
set -euo pipefail

STORAGE="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)/storage/app/public/basic-triads"

DIRS=(
  "$STORAGE/bass-guitar"
  "$STORAGE/level2/bass-guitar"
  "$STORAGE/level3/bass-guitar"
  "$STORAGE/level4/bass-guitar"
  "$STORAGE/level5/bass-guitar"
  "$STORAGE/level6/bass-guitar"
  "$STORAGE/level7/bass-guitar"
  "$STORAGE/level8/bass-guitar"
)

count=0
for dir in "${DIRS[@]}"; do
  for f in "$dir"/*.wav; do
    [ -e "$f" ] || continue
    tmp="${f}.tmp.wav"
    sox "$f" "$tmp" pitch 1200
    mv "$tmp" "$f"
    count=$((count + 1))
  done
done

echo "Shifted ${count} Bass Guitar files up one octave."
