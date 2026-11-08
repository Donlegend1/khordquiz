#!/bin/bash
# Renders real content (25 genuinely distinct rows each) for the 5
# progression quizzes that were stuck on one row padded to 25. Register:
# root at 48 for most instruments, Organ/Violin/Celesta at 60.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
STORAGE_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)/storage/app/public/chord-progressions"
VELOCITY=100
BEATS_PER_CHORD=3.2

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

PRESETS="diatonic-progression progression-build-1 progression-build-2 progression-recognition 2-5-1-chord-degree"

for preset in $PRESETS; do
  for sound in piano ep organ guitar jazz-guitar bass-guitar violin strings cello harp voice \
               trombone saxophone marimba celesta vibraphone; do
    program="$(program_for "$sound")"
    dry=""
    [ "$sound" = "voice" ] && dry="DRY=1"
    base="$(base_for "$sound")"
    outdir="$STORAGE_ROOT/$preset/$sound"
    mkdir -p "$outdir"
    while IFS=$'\t' read -r slug answer chordgroups; do
      IFS=';' read -ra groups <<< "$chordgroups"
      chordargs=()
      for g in "${groups[@]}"; do chordargs+=("$g"); done
      bash -c "$dry \"$SCRIPT_DIR/render_chord_sequence.sh\" \"$outdir/${slug}.wav\" $program $VELOCITY $BEATS_PER_CHORD ${chordargs[*]}"
    done < <(php "$SCRIPT_DIR/gen_progression_voicings.php" "$preset" "$base")
  done
  echo "done preset: $preset"
done

echo "Done."
