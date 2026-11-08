#!/bin/bash
# Renders 9th Degree Chords Levels 1-5 for the 5 newly-added instruments:
# Trombone, Saxophone (Brass), Marimba, Celesta, Vibraphone (Percussion).
# This category has no drop-2 levels — every level uses the same
# close-position base (48), so Celesta needs its usual +1 octave raise
# (base 60) uniformly across all 5 levels; the other 4 instruments use the
# standard register.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
STORAGE="$(cd "$SCRIPT_DIR/../.." && pwd)/storage/app/public/ninth-degree-chords"
VELOCITY=100
BEATS=3.2

program_for() {
  case "$1" in
    trombone) echo 57 ;; saxophone) echo 65 ;; marimba) echo 12 ;;
    celesta) echo 8 ;; vibraphone) echo 11 ;;
  esac
}

for sound in trombone saxophone marimba celesta vibraphone; do
  program="$(program_for "$sound")"
  base=48
  [ "$sound" = "celesta" ] && base=60

  for level in 1 2; do
    outdir="$STORAGE/level$level/$sound"
    mkdir -p "$outdir"
    while IFS=$'\t' read -r slug notes; do
      IFS=',' read -ra na <<< "$notes"
      "$SCRIPT_DIR/render_note.sh" "$outdir/${slug}.wav" "$program" $VELOCITY $BEATS "${na[@]}"
    done < <(php "$SCRIPT_DIR/gen_ninth_voicings.php" "$level" "$base")
    echo "done: level$level $sound"
  done

  outdir="$STORAGE/level3/$sound"
  mkdir -p "$outdir"
  while IFS=$'\t' read -r slug notes; do
    IFS=',' read -ra na <<< "$notes"
    "$SCRIPT_DIR/render_note.sh" "$outdir/${slug}.wav" "$program" $VELOCITY $BEATS "${na[@]}"
  done < <(php "$SCRIPT_DIR/gen_ninth_level3_voicings.php" "$base")
  echo "done: level3 $sound"

  outdir="$STORAGE/level4/$sound"
  mkdir -p "$outdir"
  while IFS=$'\t' read -r slug notes; do
    IFS=',' read -ra na <<< "$notes"
    "$SCRIPT_DIR/render_note.sh" "$outdir/${slug}.wav" "$program" $VELOCITY $BEATS "${na[@]}"
  done < <(php "$SCRIPT_DIR/gen_ninth_level4_voicings.php" "$base")
  echo "done: level4 $sound"

  outdir="$STORAGE/level5/$sound"
  mkdir -p "$outdir"
  while IFS=$'\t' read -r slug notes; do
    IFS=',' read -ra na <<< "$notes"
    "$SCRIPT_DIR/render_note.sh" "$outdir/${slug}.wav" "$program" $VELOCITY $BEATS "${na[@]}"
  done < <(php "$SCRIPT_DIR/gen_ninth_level5_voicings.php" "$base")
  echo "done: level5 $sound"
done

echo "Done."
