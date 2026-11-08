#!/bin/bash
# Renders 7th Degree Chords Levels 1-8 for the 5 newly-added instruments:
# Trombone, Saxophone (Brass), Marimba, Celesta, Vibraphone (Percussion).
# Close-position levels are 1,2,5,6 (base 48); drop-2 are 3,4,7,8 (base 60)
# — same split as this category's Organ fix earlier. Celesta uses base 60
# even on the close-position levels — confirmed by ear needing the same +1
# octave raise as Basic Triads/Add 9's Celesta; the other 4 new instruments
# use the standard register (no shift).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
STORAGE="$(cd "$SCRIPT_DIR/../.." && pwd)/storage/app/public/seventh-degree-chords"
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
  for level in 1 2 3 4 5 6 7 8; do
    if [ "$sound" = "celesta" ]; then
      base=60
    else
      case "$level" in
        1|2|5|6) base=48 ;;
        3|4|7|8) base=60 ;;
      esac
    fi
    outdir="$STORAGE/level$level/$sound"
    mkdir -p "$outdir"
    while IFS=$'\t' read -r slug notes; do
      IFS=',' read -ra na <<< "$notes"
      "$SCRIPT_DIR/render_note.sh" "$outdir/${slug}.wav" "$program" $VELOCITY $BEATS "${na[@]}"
    done < <(php "$SCRIPT_DIR/gen_seventh_voicings.php" "$level" "$base")
    echo "done: level$level $sound"
  done
done

echo "Done."
