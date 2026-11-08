#!/bin/bash
# Renders a chord, then its isolated highlighted tone, through GeneralUser GS:
# normalized to -8 dBFS peak, hard-trimmed to 3.8s with a 0.2s fade-out (1.6s
# chord preview + the standard 2.2s single-note render window for the tone).
#
# Usage: render_chord_then_tone.sh <out.wav> <program> <velocity> <tone_note> <chord_note1> [chord_note2 ...]
# Set DRY=1 to disable FluidSynth's chorus/reverb (used for Voice).
set -euo pipefail

OUT="$1"
PROGRAM="$2"
VELOCITY="$3"
TONE_NOTE="$4"
shift 4
CHORD_NOTES=("$@")

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SF2="${GENERALUSER_SF2:-/private/tmp/claude-501/-Users-khord-Documents-GitHub-Khordquizwebsite/024aab61-577b-471a-93e1-53dcbc70aa8d/scratchpad/soundfonts/GeneralUser.sf2}"
TMP_MID="$(mktemp -t render_chord_then_tone).mid"
TMP_WAV="$(mktemp -t render_chord_then_tone).wav"

DRY_FLAGS=""
if [ "${DRY:-0}" = "1" ]; then
  DRY_FLAGS="-C0 -R0"
fi

python3 -I "$SCRIPT_DIR/make_chord_then_tone_midi.py" "$TMP_MID" "$PROGRAM" "$VELOCITY" "$TONE_NOTE" "${CHORD_NOTES[@]}"
fluidsynth -ni $DRY_FLAGS -F "$TMP_WAV" -r 44100 -q "$SF2" "$TMP_MID"
sox "$TMP_WAV" "$OUT" gain -n -8 trim 0 3.8 fade 0 3.8 0.2

rm -f "$TMP_MID" "$TMP_WAV"
