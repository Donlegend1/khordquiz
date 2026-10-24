#!/bin/bash
# Renders a sequence of notes (e.g. a scale run) through GeneralUser GS, normalized
# to -8 dBFS peak to match render_note.sh's level, with a short fade-out instead of
# a hard trim (a sequence's own length is the point, unlike a single repeating note).
#
# Usage: render_sequence.sh <out.wav> <program> <velocity> <beats_per_note> <note1> [note2 ...]
set -euo pipefail

OUT="$1"
PROGRAM="$2"
VELOCITY="$3"
BEATS_PER_NOTE="$4"
shift 4
NOTES=("$@")

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SF2="${GENERALUSER_SF2:-/private/tmp/claude-501/-Users-khord-Documents-GitHub-Khordquizwebsite/024aab61-577b-471a-93e1-53dcbc70aa8d/scratchpad/soundfonts/GeneralUser.sf2}"
TMP_MID="$(mktemp -t render_sequence).mid"
TMP_WAV="$(mktemp -t render_sequence).wav"

python3 -I "$SCRIPT_DIR/make_sequence_midi.py" "$TMP_MID" "$PROGRAM" "$VELOCITY" "$BEATS_PER_NOTE" "${NOTES[@]}"
fluidsynth -ni -F "$TMP_WAV" -r 44100 -q "$SF2" "$TMP_MID"
sox "$TMP_WAV" "$OUT" gain -n -8 fade 0 0 0.3

rm -f "$TMP_MID" "$TMP_WAV"
