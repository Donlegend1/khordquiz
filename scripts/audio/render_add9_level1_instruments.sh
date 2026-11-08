#!/bin/bash
# Renders Add 9 · Level 1's 4 chords (Major/Minor/Diminished/Augmented add9,
# close-position root [0,2,4,7]/[0,2,3,7]/[0,2,3,6]/[0,2,4,8]) for the other
# 10 instruments, matching Basic Triads' per-instrument register convention:
#   - root 48 (Piano's register): EP, Organ, Guitar, Jazz Guitar, Strings,
#     Cello, Harp, Voice, Bass Guitar (Bass Guitar now matches Piano after
#     the earlier Basic Triads fix — no longer a full octave down).
#   - root 60 (+1 octave): Violin — its open G string (MIDI 55) sits above
#     Piano's root, a real instrument-range reason, unchanged from Basic
#     Triads Level 5's original convention.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
STORAGE="$(cd "$SCRIPT_DIR/../.." && pwd)/storage/app/public/add9"
VELOCITY=100
BEATS=3.2

# slug => interval list (comma-separated, relative to root)
QUALITIES="major:0,2,4,7 minor:0,2,3,7 diminished:0,2,3,6 augmented:0,2,4,8"

program_for() {
  case "$1" in
    ep) echo 4 ;; organ) echo 16 ;; guitar) echo 25 ;; jazz-guitar) echo 26 ;;
    bass-guitar) echo 33 ;; violin) echo 40 ;; strings) echo 48 ;; cello) echo 42 ;;
    harp) echo 46 ;; voice) echo 52 ;;
  esac
}

root_for() {
  case "$1" in
    violin) echo 60 ;;
    *) echo 48 ;;
  esac
}

for sound in ep organ guitar jazz-guitar bass-guitar violin strings cello harp voice; do
  program="$(program_for "$sound")"
  root="$(root_for "$sound")"
  outdir="$STORAGE/$sound"
  mkdir -p "$outdir"
  dry=""
  [ "$sound" = "voice" ] && dry="DRY=1"
  for entry in $QUALITIES; do
    slug="${entry%%:*}"
    intervals="${entry##*:}"
    IFS=',' read -ra iv <<< "$intervals"
    notes=""
    for i in "${iv[@]}"; do notes="$notes $((root + i))"; done
    out="$outdir/${slug}.wav"
    bash -c "$dry \"$SCRIPT_DIR/render_note.sh\" \"$out\" $program $VELOCITY $BEATS$notes"
    echo "rendered $out"
  done
done

echo "Done."
