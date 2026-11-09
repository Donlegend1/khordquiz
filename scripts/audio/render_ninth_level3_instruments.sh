#!/bin/bash
# Renders "9th Degree Chords · Level 3" (root fixed in bass, upper-structure
# 7th chord inverted on top, 6 qualities x 4 positions) across all 11
# instruments. Register: root at 48 for most instruments, Organ and Violin
# at 60 (same established per-instrument convention as Levels 1-2).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
STORAGE="$(cd "$SCRIPT_DIR/../.." && pwd)/storage/app/public/ninth-degree-chords/level3"
VELOCITY=100
BEATS=3.2

program_for() {
  case "$1" in
    piano) echo 0 ;; ep) echo 4 ;; organ) echo 16 ;; guitar) echo 25 ;; jazz-guitar) echo 26 ;;
    bass-guitar) echo 33 ;; violin) echo 40 ;; strings) echo 48 ;; cello) echo 42 ;;
    harp) echo 46 ;; voice) echo 52 ;;
  esac
}

base_for() {
  case "$1" in
    violin|organ) echo 60 ;;
    *) echo 48 ;;
  esac
}

for sound in piano ep organ guitar jazz-guitar bass-guitar violin strings cello harp voice; do
  program="$(program_for "$sound")"
  dry=""
  [ "$sound" = "voice" ] && dry="DRY=1"
  base="$(base_for "$sound")"
  outdir="$STORAGE/$sound"
  mkdir -p "$outdir"
  while IFS=$'\t' read -r slug notes; do
    IFS=',' read -ra na <<< "$notes"
    bash -c "$dry \"$SCRIPT_DIR/render_note.sh\" \"$outdir/${slug}.wav\" $program $VELOCITY $BEATS ${na[*]}"
  done < <(php "$SCRIPT_DIR/gen_ninth_level3_voicings.php" "$base")
  echo "done: $sound"
done

echo "Done."
