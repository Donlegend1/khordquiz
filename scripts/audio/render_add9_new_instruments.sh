#!/bin/bash
# Renders Add 9 Levels 1-8 for the 5 newly-added instruments: Trombone,
# Saxophone (Brass), Marimba, Celesta, Vibraphone (Percussion).
# Add 9's close-position levels are 1-4 (base 48), drop-2 are 5-8 (base 60)
# — note this is a DIFFERENT level split than Basic Triads (whose drop-2
# levels are 3/4/7/8), so levels 1-4 use the close base here.
# Celesta uses base 60 even on the close-position levels (1-4) — confirmed
# by ear needing the same +1 octave raise Basic Triads' Celesta needed;
# Trombone/Saxophone/Marimba/Vibraphone use the standard register (no
# shift needed, same as their Basic Triads treatment).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
STORAGE="$(cd "$SCRIPT_DIR/../.." && pwd)/storage/app/public/add9"
VELOCITY=100
BEATS=3.2

program_for() {
  case "$1" in
    trombone) echo 57 ;; saxophone) echo 65 ;; marimba) echo 12 ;;
    celesta) echo 8 ;; vibraphone) echo 11 ;;
  esac
}

level_dir() {
  case "$1" in
    1) echo "$STORAGE" ;;
    *) echo "$STORAGE/level$1" ;;
  esac
}

for sound in trombone saxophone marimba celesta vibraphone; do
  program="$(program_for "$sound")"
  for level in 1 2 3 4 5 6 7 8; do
    if [ "$sound" = "celesta" ]; then
      base=60
    else
      case "$level" in
        1|2|3|4) base=48 ;;
        5|6|7|8) base=60 ;;
      esac
    fi
    outdir="$(level_dir "$level")/$sound"
    mkdir -p "$outdir"
    while IFS=$'\t' read -r slug notes; do
      IFS=',' read -ra na <<< "$notes"
      "$SCRIPT_DIR/render_note.sh" "$outdir/${slug}.wav" "$program" $VELOCITY $BEATS "${na[@]}"
    done < <(php "$SCRIPT_DIR/gen_add9_voicings.php" "$level" "$base")
    echo "done: level$level $sound"
  done
done

echo "Done."
