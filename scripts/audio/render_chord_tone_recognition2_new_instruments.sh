#!/bin/bash
# Renders "Chord Tone Recognition #2" for the 10 instruments beyond
# Extensions' original 6 (Piano, Organ, Guitar, Strings, Voice, Marimba) —
# bringing it up to the full 16-instrument set used throughout the
# "Degree Chords" series. Register: root at 48 for most instruments,
# Violin/Celesta at 60.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
STORAGE="$(cd "$SCRIPT_DIR/../.." && pwd)/storage/app/public/chord-tone-recognition-2"
VELOCITY=100

program_for() {
  case "$1" in
    ep) echo 4 ;; jazz-guitar) echo 26 ;; bass-guitar) echo 33 ;; violin) echo 40 ;;
    cello) echo 42 ;; harp) echo 46 ;; trombone) echo 57 ;; saxophone) echo 65 ;;
    celesta) echo 8 ;; vibraphone) echo 11 ;;
  esac
}

base_for() {
  case "$1" in
    violin|celesta) echo 60 ;;
    *) echo 48 ;;
  esac
}

for sound in ep jazz-guitar bass-guitar violin cello harp trombone saxophone celesta vibraphone; do
  program="$(program_for "$sound")"
  base="$(base_for "$sound")"
  outdir="$STORAGE/$sound"
  mkdir -p "$outdir"
  while IFS=$'\t' read -r slug qlabel tone chordnotes; do
    IFS=',' read -ra cn <<< "$chordnotes"
    bash -c "\"$SCRIPT_DIR/render_chord_then_tone.sh\" \"$outdir/${slug}.wav\" $program $VELOCITY $tone ${cn[*]}"
  done < <(php "$SCRIPT_DIR/gen_chord_tone_recognition2_voicings.php" "$base")
  echo "done: $sound"
done

echo "Done."
