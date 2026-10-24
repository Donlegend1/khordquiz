#!/bin/bash
# Renders a sequence of block chords through GeneralUser GS, normalized to
# -8 dBFS peak to match render_note.sh/render_sequence.sh's level, with a
# short fade-out. Unlike render_sequence.sh (one note at a time), each chord
# argument here is a comma-separated group of simultaneous notes.
#
# Usage: render_chord_sequence.sh <out.wav> <program> <velocity> <beats_per_chord> <chord1> [chord2 ...]
# Each chordN is e.g. "60,64,67".
# Set DRY=1 to disable FluidSynth's chorus/reverb (used for Voice — see render_note.sh).
set -euo pipefail

OUT="$1"
PROGRAM="$2"
VELOCITY="$3"
BEATS_PER_CHORD="$4"
shift 4
CHORDS=("$@")

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SF2="${GENERALUSER_SF2:-/private/tmp/claude-501/-Users-khord-Documents-GitHub-Khordquizwebsite/024aab61-577b-471a-93e1-53dcbc70aa8d/scratchpad/soundfonts/GeneralUser.sf2}"
TMP_MID="$(mktemp -t render_chord_sequence).mid"
TMP_WAV="$(mktemp -t render_chord_sequence).wav"

DRY_FLAGS=""
if [ "${DRY:-0}" = "1" ]; then
  DRY_FLAGS="-C0 -R0"
fi

python3 -I "$SCRIPT_DIR/make_chord_sequence_midi.py" "$TMP_MID" "$PROGRAM" "$VELOCITY" "$BEATS_PER_CHORD" "${CHORDS[@]}"
fluidsynth -ni $DRY_FLAGS -F "$TMP_WAV" -r 44100 -q "$SF2" "$TMP_MID"
sox "$TMP_WAV" "$OUT" gain -n -8 fade 0 0 0.3

rm -f "$TMP_MID" "$TMP_WAV"
