#!/bin/bash
# Renders "Chord Tone Recognition #2" (1st/2nd/3rd inversion chord, then
# isolated highlighted tone) across Extensions' 6 instruments. Register:
# root at 48 for most instruments, Organ at 60.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
STORAGE="$(cd "$SCRIPT_DIR/../.." && pwd)/storage/app/public/chord-tone-recognition-2"
VELOCITY=100

program_for() {
  case "$1" in
    piano) echo 0 ;; organ) echo 16 ;; guitar) echo 25 ;;
    strings) echo 48 ;; voice) echo 52 ;; marimba) echo 12 ;;
  esac
}

base_for() {
  case "$1" in
    organ) echo 60 ;;
    *) echo 48 ;;
  esac
}

for sound in piano organ guitar strings voice marimba; do
  program="$(program_for "$sound")"
  dry=""
  [ "$sound" = "voice" ] && dry="DRY=1"
  base="$(base_for "$sound")"
  outdir="$STORAGE/$sound"
  mkdir -p "$outdir"
  while IFS=$'\t' read -r slug qlabel tone chordnotes; do
    IFS=',' read -ra cn <<< "$chordnotes"
    bash -c "$dry \"$SCRIPT_DIR/render_chord_then_tone.sh\" \"$outdir/${slug}.wav\" $program $VELOCITY $tone ${cn[*]}"
  done < <(php "$SCRIPT_DIR/gen_chord_tone_recognition2_voicings.php" "$base")
  echo "done: $sound"
done

echo "Done."
