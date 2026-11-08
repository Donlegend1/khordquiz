#!/bin/bash
# Renders all 6 "Secondary 9th Chords" levels across all 16 instruments
# (the original 11 + the 5 later additions: Trombone, Saxophone, Marimba,
# Celesta, Vibraphone). Same register convention as 9th Degree Chords: root
# at 48 for most instruments, Organ/Violin/Celesta at 60 (no drop-2 levels
# exist in this category).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
STORAGE="$(cd "$SCRIPT_DIR/../.." && pwd)/storage/app/public/secondary-ninth-chords"
VELOCITY=100
BEATS=3.2

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

for sound in piano ep organ guitar jazz-guitar bass-guitar violin strings cello harp voice \
             trombone saxophone marimba celesta vibraphone; do
  program="$(program_for "$sound")"
  dry=""
  [ "$sound" = "voice" ] && dry="DRY=1"
  base="$(base_for "$sound")"

  for level in 1 2; do
    outdir="$STORAGE/level$level/$sound"
    mkdir -p "$outdir"
    while IFS=$'\t' read -r slug notes; do
      IFS=',' read -ra na <<< "$notes"
      bash -c "$dry \"$SCRIPT_DIR/render_note.sh\" \"$outdir/${slug}.wav\" $program $VELOCITY $BEATS ${na[*]}"
    done < <(php "$SCRIPT_DIR/gen_secondary_ninth_voicings.php" "$level" "$base")
    echo "done: level$level $sound"
  done

  outdir="$STORAGE/level3/$sound"
  mkdir -p "$outdir"
  while IFS=$'\t' read -r slug notes; do
    IFS=',' read -ra na <<< "$notes"
    bash -c "$dry \"$SCRIPT_DIR/render_note.sh\" \"$outdir/${slug}.wav\" $program $VELOCITY $BEATS ${na[*]}"
  done < <(php "$SCRIPT_DIR/gen_secondary_ninth_level3_voicings.php" "$base")
  echo "done: level3 $sound"

  outdir="$STORAGE/level4/$sound"
  mkdir -p "$outdir"
  while IFS=$'\t' read -r slug notes; do
    IFS=',' read -ra na <<< "$notes"
    bash -c "$dry \"$SCRIPT_DIR/render_note.sh\" \"$outdir/${slug}.wav\" $program $VELOCITY $BEATS ${na[*]}"
  done < <(php "$SCRIPT_DIR/gen_secondary_ninth_level4_voicings.php" "$base")
  echo "done: level4 $sound"

  outdir="$STORAGE/level5/$sound"
  mkdir -p "$outdir"
  while IFS=$'\t' read -r slug notes; do
    IFS=',' read -ra na <<< "$notes"
    bash -c "$dry \"$SCRIPT_DIR/render_note.sh\" \"$outdir/${slug}.wav\" $program $VELOCITY $BEATS ${na[*]}"
  done < <(php "$SCRIPT_DIR/gen_secondary_ninth_level5_voicings.php" "$base")
  echo "done: level5 $sound"

  outdir="$STORAGE/level6/$sound"
  mkdir -p "$outdir"
  while IFS=$'\t' read -r slug notes; do
    IFS=',' read -ra na <<< "$notes"
    bash -c "$dry \"$SCRIPT_DIR/render_note.sh\" \"$outdir/${slug}.wav\" $program $VELOCITY $BEATS ${na[*]}"
  done < <(php "$SCRIPT_DIR/gen_secondary_ninth_level6_voicings.php" "$base")
  echo "done: level6 $sound"
done

echo "Done."
