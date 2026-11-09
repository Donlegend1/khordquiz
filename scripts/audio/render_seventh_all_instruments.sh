#!/bin/bash
# Renders all 8 "7th Degree Chords" levels across all 11 instruments.
# Register convention matches Basic Triads/Add 9: close-position levels
# (1,2,5,6) root at 48 for everyone except Violin at 60; drop-2 levels
# (3,4,7,8) root at 60 for everyone except Violin at 72.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
STORAGE="$(cd "$SCRIPT_DIR/../.." && pwd)/storage/app/public/seventh-degree-chords"
VELOCITY=100
BEATS=3.2

program_for() {
  case "$1" in
    piano) echo 0 ;; ep) echo 4 ;; organ) echo 16 ;; guitar) echo 25 ;; jazz-guitar) echo 26 ;;
    bass-guitar) echo 33 ;; violin) echo 40 ;; strings) echo 48 ;; cello) echo 42 ;;
    harp) echo 46 ;; voice) echo 52 ;;
  esac
}

close_base_for() {
  case "$1" in
    violin) echo 60 ;;
    *) echo 48 ;;
  esac
}

drop2_base_for() {
  case "$1" in
    violin) echo 72 ;;
    *) echo 60 ;;
  esac
}

for sound in piano ep organ guitar jazz-guitar bass-guitar violin strings cello harp voice; do
  program="$(program_for "$sound")"
  dry=""
  [ "$sound" = "voice" ] && dry="DRY=1"
  close_base="$(close_base_for "$sound")"
  drop2_base="$(drop2_base_for "$sound")"

  for level in 1 2 3 4 5 6 7 8; do
    case "$level" in
      1|2|5|6) base="$close_base" ;;
      3|4|7|8) base="$drop2_base" ;;
    esac
    outdir="$STORAGE/level${level}/$sound"
    mkdir -p "$outdir"
    while IFS=$'\t' read -r slug notes; do
      IFS=',' read -ra na <<< "$notes"
      bash -c "$dry \"$SCRIPT_DIR/render_note.sh\" \"$outdir/${slug}.wav\" $program $VELOCITY $BEATS ${na[*]}"
    done < <(php "$SCRIPT_DIR/gen_seventh_voicings.php" "$level" "$base")
    echo "done: level$level $sound"
  done
done

echo "Done."
