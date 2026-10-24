#!/bin/bash
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/../.."

OUT=storage/app/public/basic-triads/level2/voice
mkdir -p "$OUT"

declare -a KEYS=("c:0" "db:1" "d:2" "eb:3" "e:4" "f:5" "gb:6" "g:7" "ab:8" "a:9" "bb:10" "b:11")

for keyentry in "${KEYS[@]}"; do
  keyname="${keyentry%%:*}"
  offset="${keyentry##*:}"
  root=$((48 + offset))
  DRY=1 bash scripts/audio/render_note.sh "$OUT/${keyname}-major.wav" 52 90 3.2 $root $((root+4)) $((root+7))
  DRY=1 bash scripts/audio/render_note.sh "$OUT/${keyname}-minor.wav" 52 90 3.2 $root $((root+3)) $((root+7))
  DRY=1 bash scripts/audio/render_note.sh "$OUT/${keyname}-diminished.wav" 52 90 3.2 $root $((root+3)) $((root+6))
  DRY=1 bash scripts/audio/render_note.sh "$OUT/${keyname}-augmented.wav" 52 90 3.2 $root $((root+4)) $((root+8))
  DRY=1 bash scripts/audio/render_note.sh "$OUT/${keyname}-sus4.wav" 52 90 3.2 $root $((root+5)) $((root+7))
  DRY=1 bash scripts/audio/render_note.sh "$OUT/${keyname}-sus2.wav" 52 90 3.2 $root $((root+2)) $((root+7))
done
echo "done: voice ($(ls "$OUT" | wc -l | tr -d ' ') files)"
