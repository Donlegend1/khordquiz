#!/bin/bash
# Renders the other 10 instruments (everything but Piano) for Add 9 Levels
# 4-8, using gen_add9_voicings.php for the note math. Register convention
# matches Levels 1-3/5 already done: close-position levels (4) root at 48,
# drop-2 levels (5-8) root at 60 — for everyone except Violin, which sits a
# full octave higher in both cases (its open-string range), same as every
# other Add 9/Basic Triads level so far.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
STORAGE="$(cd "$SCRIPT_DIR/../.." && pwd)/storage/app/public/add9"
VELOCITY=100
BEATS=3.2

program_for() {
  case "$1" in
    ep) echo 4 ;; organ) echo 16 ;; guitar) echo 25 ;; jazz-guitar) echo 26 ;;
    bass-guitar) echo 33 ;; violin) echo 40 ;; strings) echo 48 ;; cello) echo 42 ;;
    harp) echo 46 ;; voice) echo 52 ;;
  esac
}

# close-position base (Levels 1-4) per instrument
close_base_for() {
  case "$1" in
    violin) echo 60 ;;
    *) echo 48 ;;
  esac
}

# drop-2 base (Levels 5-8) per instrument
drop2_base_for() {
  case "$1" in
    violin) echo 72 ;;
    *) echo 60 ;;
  esac
}

for sound in ep organ guitar jazz-guitar bass-guitar violin strings cello harp voice; do
  program="$(program_for "$sound")"
  dry=""
  [ "$sound" = "voice" ] && dry="DRY=1"

  close_base="$(close_base_for "$sound")"
  drop2_base="$(drop2_base_for "$sound")"

  # Level 4: 12 keys, all positions, no drop-2
  outdir="$STORAGE/level4/$sound"
  mkdir -p "$outdir"
  while IFS=$'\t' read -r slug notes; do
    IFS=',' read -ra na <<< "$notes"
    bash -c "$dry \"$SCRIPT_DIR/render_note.sh\" \"$outdir/${slug}.wav\" $program $VELOCITY $BEATS ${na[*]}"
  done < <(php "$SCRIPT_DIR/gen_add9_voicings.php" 4 "$close_base")
  echo "done: level4 $sound"

  # Level 5: C only, root position, drop-2
  outdir="$STORAGE/level5/$sound"
  mkdir -p "$outdir"
  while IFS=$'\t' read -r slug notes; do
    IFS=',' read -ra na <<< "$notes"
    bash -c "$dry \"$SCRIPT_DIR/render_note.sh\" \"$outdir/${slug}.wav\" $program $VELOCITY $BEATS ${na[*]}"
  done < <(php "$SCRIPT_DIR/gen_add9_voicings.php" 5 "$drop2_base")
  echo "done: level5 $sound"

  # Level 6: 12 keys, root position, drop-2
  outdir="$STORAGE/level6/$sound"
  mkdir -p "$outdir"
  while IFS=$'\t' read -r slug notes; do
    IFS=',' read -ra na <<< "$notes"
    bash -c "$dry \"$SCRIPT_DIR/render_note.sh\" \"$outdir/${slug}.wav\" $program $VELOCITY $BEATS ${na[*]}"
  done < <(php "$SCRIPT_DIR/gen_add9_voicings.php" 6 "$drop2_base")
  echo "done: level6 $sound"

  # Level 7: C only, all positions, drop-2
  outdir="$STORAGE/level7/$sound"
  mkdir -p "$outdir"
  while IFS=$'\t' read -r slug notes; do
    IFS=',' read -ra na <<< "$notes"
    bash -c "$dry \"$SCRIPT_DIR/render_note.sh\" \"$outdir/${slug}.wav\" $program $VELOCITY $BEATS ${na[*]}"
  done < <(php "$SCRIPT_DIR/gen_add9_voicings.php" 7 "$drop2_base")
  echo "done: level7 $sound"

  # Level 8: 12 keys, all positions, drop-2
  outdir="$STORAGE/level8/$sound"
  mkdir -p "$outdir"
  while IFS=$'\t' read -r slug notes; do
    IFS=',' read -ra na <<< "$notes"
    bash -c "$dry \"$SCRIPT_DIR/render_note.sh\" \"$outdir/${slug}.wav\" $program $VELOCITY $BEATS ${na[*]}"
  done < <(php "$SCRIPT_DIR/gen_add9_voicings.php" 8 "$drop2_base")
  echo "done: level8 $sound"
done

echo "Done."
