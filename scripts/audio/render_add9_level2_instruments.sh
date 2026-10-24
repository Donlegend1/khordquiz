#!/bin/bash
# Renders Add 9 · Level 2's 4 chords across all 12 keys (C through B
# chromatically) for all 11 instruments — same close-position voicing as
# Level 1 ([0,2,4,7]/[0,2,3,7]/[0,2,3,6]/[0,2,4,8]), same per-instrument
# register convention (root 48 for everyone except Violin at 60).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
STORAGE="$(cd "$SCRIPT_DIR/../.." && pwd)/storage/app/public/add9/level2"
VELOCITY=100
BEATS=3.2

QUALITIES="major:0,2,4,7 minor:0,2,3,7 diminished:0,2,3,6 augmented:0,2,4,8"
KEYS="c:0 db:1 d:2 eb:3 e:4 f:5 gb:6 g:7 ab:8 a:9 bb:10 b:11"

program_for() {
  case "$1" in
    piano) echo 0 ;; ep) echo 4 ;; organ) echo 16 ;; guitar) echo 25 ;; jazz-guitar) echo 26 ;;
    bass-guitar) echo 33 ;; violin) echo 40 ;; strings) echo 48 ;; cello) echo 42 ;;
    harp) echo 46 ;; voice) echo 52 ;;
  esac
}

base_for() {
  case "$1" in
    violin) echo 60 ;;
    *) echo 48 ;;
  esac
}

for sound in piano ep organ guitar jazz-guitar bass-guitar violin strings cello harp voice; do
  program="$(program_for "$sound")"
  base="$(base_for "$sound")"
  outdir="$STORAGE/$sound"
  mkdir -p "$outdir"
  dry=""
  [ "$sound" = "voice" ] && dry="DRY=1"
  for kentry in $KEYS; do
    keyslug="${kentry%%:*}"
    keyoffset="${kentry##*:}"
    root=$((base + keyoffset))
    for qentry in $QUALITIES; do
      slug="${qentry%%:*}"
      intervals="${qentry##*:}"
      IFS=',' read -ra iv <<< "$intervals"
      notes=""
      for i in "${iv[@]}"; do notes="$notes $((root + i))"; done
      out="$outdir/${keyslug}-${slug}.wav"
      bash -c "$dry \"$SCRIPT_DIR/render_note.sh\" \"$out\" $program $VELOCITY $BEATS$notes"
    done
  done
  echo "done: $sound"
done

echo "Done."
