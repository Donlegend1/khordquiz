#!/bin/bash
# Renders Add 9 · Level 4's Piano audio: 12 keys x 4 qualities x 4 rotated
# positions (same rotation technique as Level 3, just across all 12 keys
# instead of just C) = 192 files. Other instruments follow on request.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
OUT="$(cd "$SCRIPT_DIR/../.." && pwd)/storage/app/public/add9/level4/piano"
mkdir -p "$OUT"
VELOCITY=100
BEATS=3.2
BASE=48

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

    "$SCRIPT_DIR/render_note.sh" "$OUT/${keyslug}-${qslug}-root.wav" 0 $VELOCITY $BEATS $n0 $n1 $n2 $n3
    "$SCRIPT_DIR/render_note.sh" "$OUT/${keyslug}-${qslug}-1st.wav" 0 $VELOCITY $BEATS $n1 $n2 $n3 $((n0+12))
    "$SCRIPT_DIR/render_note.sh" "$OUT/${keyslug}-${qslug}-2nd.wav" 0 $VELOCITY $BEATS $n2 $n3 $((n0+12)) $((n1+12))
    "$SCRIPT_DIR/render_note.sh" "$OUT/${keyslug}-${qslug}-3rd.wav" 0 $VELOCITY $BEATS $n3 $((n0+12)) $((n1+12)) $((n2+12))
  done
  echo "done key: $keyslug"
done

echo "Done."
