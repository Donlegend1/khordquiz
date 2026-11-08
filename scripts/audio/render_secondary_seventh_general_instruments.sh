#!/bin/bash
# Renders "Secondary 7th Chords · Level 9" (General) across all 11
# instruments — 11 qualities x 12 keys x 4 positions, close-position voicing.
# Register: root at 48 for most instruments, Organ and Violin at 60 (same
# per-instrument register convention established for this category's other
# levels — Organ's close-position chords at C3 sound muddy on this GM
# Drawbar Organ patch; Violin's open-string floor sits above C3).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
STORAGE="$(cd "$SCRIPT_DIR/../.." && pwd)/storage/app/public/secondary-seventh-chords/level9"
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
  done < <(php "$SCRIPT_DIR/gen_secondary_seventh_general_voicings.php" "$base")
  echo "done: $sound"
done

echo "Done."
