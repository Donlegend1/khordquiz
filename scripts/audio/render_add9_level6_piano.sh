#!/bin/bash
# Renders Add 9 · Level 6's Piano audio: 12 keys x 4 qualities, drop-2
# voicing (same transform as Level 5, based on C4/MIDI 60) — same
# relationship Level 5 has to Level 1, but Level 6 has to Level 2.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
OUT="$(cd "$SCRIPT_DIR/../.." && pwd)/storage/app/public/add9/level6/piano"
mkdir -p "$OUT"
VELOCITY=100
BEATS=3.2
BASE=60

QUALITIES="major:0,2,4,7 minor:0,2,3,7 diminished:0,2,3,6 augmented:0,2,4,8"
KEYS="c:0 db:1 d:2 eb:3 e:4 f:5 gb:6 g:7 ab:8 a:9 bb:10 b:11"

for kentry in $KEYS; do
  keyslug="${kentry%%:*}"
  keyoffset="${kentry##*:}"
  root=$((BASE + keyoffset))
  for qentry in $QUALITIES; do
    qslug="${qentry%%:*}"
    intervals="${qentry##*:}"
    IFS=',' read -ra iv <<< "$intervals"
    n0=$((root + iv[0])); n1=$((root + iv[1])); n2=$((root + iv[2])); n3=$((root + iv[3]))
    # drop-2: drop the 2nd-from-top note (index 2 of 4, 0-indexed) an octave.
    dropped=$((n2 - 12))
    # re-sort ascending: dropped, n0, n1, n3 (dropped lands below n0 for all these voicings)
    "$SCRIPT_DIR/render_note.sh" "$OUT/${keyslug}-${qslug}.wav" 0 $VELOCITY $BEATS $dropped $n0 $n1 $n3
  done
  echo "done key: $keyslug"
done

echo "Done."
