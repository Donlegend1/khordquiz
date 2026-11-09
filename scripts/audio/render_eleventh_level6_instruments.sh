#!/bin/bash
# Renders "11th Degree Chords · Level 6" (12-key version of Level 5) across
# all 16 instruments. Register: root at 48 for most instruments,
# Organ/Violin/Celesta at 60.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
STORAGE="$(cd "$SCRIPT_DIR/../.." && pwd)/storage/app/public/eleventh-degree-chords/level6"
VELOCITY=100
BEATS=3.2

program_for() {
  case "$1" in
    piano) echo 0 ;; ep) echo 4 ;; organ) echo 16 ;; guitar) echo 25 ;; jazz-guitar) echo 26 ;;
    bass-guitar) echo 33 ;; violin) echo 40 ;; strings) echo 48 ;; cello) echo 42 ;;
    harp) echo 46 ;; voice) echo 52 ;;
    trombone) echo 57 ;; saxophone) echo 65 ;; marimba) echo 12 ;;
    celesta) echo 8 ;; vibraphone) echo 11 ;;
  esac
}

base_for() {
  case "$1" in
    violin|organ|celesta) echo 60 ;;
    *) echo 48 ;;
  esac
}

for sound in piano ep organ guitar jazz-guitar bass-guitar violin strings cello harp voice \
             trombone saxophone marimba celesta vibraphone; do
  program="$(program_for "$sound")"
  dry=""
  [ "$sound" = "voice" ] && dry="DRY=1"
  base="$(base_for "$sound")"
  outdir="$STORAGE/$sound"
  mkdir -p "$outdir"
  while IFS=$'\t' read -r slug notes; do
    IFS=',' read -ra na <<< "$notes"
    bash -c "$dry \"$SCRIPT_DIR/render_note.sh\" \"$outdir/${slug}.wav\" $program $VELOCITY $BEATS ${na[*]}"
  done < <(php "$SCRIPT_DIR/gen_eleventh_level6_voicings.php" "$base")
  echo "done: $sound"
done

echo "Done."
