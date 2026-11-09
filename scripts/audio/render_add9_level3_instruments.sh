#!/bin/bash
# Renders Add 9 · Level 3's 16 chords (4 qualities x 4 rotated positions,
# key of C) for the other 10 instruments, same per-instrument register
# convention as Levels 1-2 (root 48 for everyone except Violin at 60).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
STORAGE="$(cd "$SCRIPT_DIR/../.." && pwd)/storage/app/public/add9/level3"
VELOCITY=100
BEATS=3.2

# quality:intervals (root-position, comma-separated)
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
  for qentry in $QUALITIES; do
    qslug="${qentry%%:*}"
    intervals="${qentry##*:}"
    IFS=',' read -ra iv <<< "$intervals"
    n0=$((root + iv[0])); n1=$((root + iv[1])); n2=$((root + iv[2])); n3=$((root + iv[3]))

    bash -c "$dry \"$SCRIPT_DIR/render_note.sh\" \"$outdir/${qslug}-root.wav\" $program $VELOCITY $BEATS $n0 $n1 $n2 $n3"
    bash -c "$dry \"$SCRIPT_DIR/render_note.sh\" \"$outdir/${qslug}-1st.wav\" $program $VELOCITY $BEATS $n1 $n2 $n3 $((n0+12))"
    bash -c "$dry \"$SCRIPT_DIR/render_note.sh\" \"$outdir/${qslug}-2nd.wav\" $program $VELOCITY $BEATS $n2 $n3 $((n0+12)) $((n1+12))"
    bash -c "$dry \"$SCRIPT_DIR/render_note.sh\" \"$outdir/${qslug}-3rd.wav\" $program $VELOCITY $BEATS $n3 $((n0+12)) $((n1+12)) $((n2+12))"
  done
  echo "done: $sound"
done

echo "Done."
