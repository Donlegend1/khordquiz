#!/bin/bash
# Renders all 8 "Secondary 7th Chords" levels across all 11 instruments.
# Register convention: close-position levels (1,2,5,6) root at 48 for most
# instruments, EXCEPT Organ and Violin which use 60 — Organ because a
# close-position 4-note chord at C3 on this GM Drawbar Organ patch sounds
# muddy (confirmed/fixed on both Add 9 and 7th Degree Chords this session,
# baked in here from the start instead of needing a follow-up fix); Violin
# because its open-string floor sits above C3. Drop-2 levels (3,4,7,8) root
# at 60 for everyone except Violin at 72 (same +12 exception, consistent
# register above the drop-2 baseline).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
STORAGE="$(cd "$SCRIPT_DIR/../.." && pwd)/storage/app/public/secondary-seventh-chords"
VELOCITY=100
BEATS=3.2

program_for() {
  case "$1" in
    piano) echo 0 ;; ep) echo 4 ;; organ) echo 16 ;; guitar) echo 25 ;; jazz-guitar) echo 26 ;;
    bass-guitar) echo 33 ;; violin) echo 40 ;; strings) echo 48 ;; cello) echo 42 ;;
    harp) echo 46 ;; voice) echo 52 ;;
  esac
}

close_base_for() {
  case "$1" in
    violin|organ) echo 60 ;;
    *) echo 48 ;;
  esac
}

drop2_base_for() {
  case "$1" in
    violin) echo 72 ;;
    *) echo 60 ;;
  esac
}

for sound in piano ep organ guitar jazz-guitar bass-guitar violin strings cello harp voice; do
  program="$(program_for "$sound")"
  dry=""
  [ "$sound" = "voice" ] && dry="DRY=1"
  close_base="$(close_base_for "$sound")"
  drop2_base="$(drop2_base_for "$sound")"

  for level in 1 2 3 4 5 6 7 8; do
    case "$level" in
      1|2|5|6) base="$close_base" ;;
      3|4|7|8) base="$drop2_base" ;;
    esac
    outdir="$STORAGE/level${level}/$sound"
    mkdir -p "$outdir"
    while IFS=$'\t' read -r slug notes; do
      IFS=',' read -ra na <<< "$notes"
      bash -c "$dry \"$SCRIPT_DIR/render_note.sh\" \"$outdir/${slug}.wav\" $program $VELOCITY $BEATS ${na[*]}"
    done < <(php "$SCRIPT_DIR/gen_secondary_seventh_voicings.php" "$level" "$base")
    echo "done: level$level $sound"
  done
done

echo "Done."
