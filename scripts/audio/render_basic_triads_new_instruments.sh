#!/bin/bash
# Renders Basic Triads Levels 1-8 for the 5 newly-added instruments:
# Trombone, Saxophone (Brass), Marimba, Celesta, Vibraphone (Percussion).
# Standard register (base 48 close-position / 60 drop-2) — no special shift,
# same treatment as every other "normal" instrument in this category
# (EP, Guitar, Jazz Guitar, Strings, Cello, Harp, Voice, Bass Guitar).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
STORAGE="$(cd "$SCRIPT_DIR/../.." && pwd)/storage/app/public/basic-triads"
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

close_base=48
drop2_base=60

for sound in trombone saxophone marimba celesta vibraphone; do
  program="$(program_for "$sound")"
  for level in 1 2 3 4 5 6 7 8; do
    case "$level" in
      1|2|5|6) base="$close_base" ;;
      3|4|7|8) base="$drop2_base" ;;
    esac
    outdir="$(level_dir "$level")/$sound"
    mkdir -p "$outdir"
    while IFS=$'\t' read -r slug notes; do
      IFS=',' read -ra na <<< "$notes"
      "$SCRIPT_DIR/render_note.sh" "$outdir/${slug}.wav" "$program" $VELOCITY $BEATS "${na[@]}"
    done < <(php "$SCRIPT_DIR/gen_basic_triads_new_instruments_voicings.php" "$level" "$base")
    echo "done: level$level $sound"
  done
done

echo "Done."
