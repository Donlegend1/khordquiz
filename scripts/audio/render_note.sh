#!/bin/bash
# Renders one note/chord through GeneralUser GS: normalized to -8 dBFS peak, hard-
# trimmed to 2.2s with a 0.2s fade-out.
#
# FluidSynth's default gain leaves raw renders far too quiet (~-25 to -31 dBFS peak,
# confirmed via `sox -n stat`) — every render must go through this, not fluidsynth
# directly, or the result will be inaudibly faint next to normal audio content.
# -8 dBFS (not -3) per a later volume pass: -3 was normalized first, then every
# generated note across every sound got an additional -5 dB trim — -8 bakes both
# steps into one so future renders land at the same level without a second pass.
#
# The trim+fade matters for anything that autoplays several times in a row (the
# quiz player repeats each question's audio 3x): FluidSynth renders the full
# natural decay tail, which for a long-sustain instrument (marimba measured at
# 9+ seconds) is mostly inaudible near-silence — left in, that tail becomes a long
# dead gap between repeats, since the next play only starts once the browser fires
# "ended" at the very end of the file. 2.2s total (beats=3.2 is the standard note
# hold used so far, ~1.6s) plus a quick fade avoids both the dead air and an
# abrupt click from a hard cutoff mid-decay.
#
# Usage: render_note.sh <out.wav> <program> <velocity> <beats> <note1> [note2 ...]
# Set DRY=1 to disable FluidSynth's chorus/reverb (used for Voice — see note-sounds
# notes elsewhere: the Choir Aahs patch's reverb smeared multiple questions
# together more than other instruments tolerated).
set -euo pipefail

OUT="$1"
PROGRAM="$2"
VELOCITY="$3"
BEATS="$4"
shift 4
NOTES=("$@")

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SF2="${GENERALUSER_SF2:-/private/tmp/claude-501/-Users-khord-Documents-GitHub-Khordquizwebsite/024aab61-577b-471a-93e1-53dcbc70aa8d/scratchpad/soundfonts/GeneralUser.sf2}"
TMP_MID="$(mktemp -t render_note).mid"
TMP_WAV="$(mktemp -t render_note).wav"

DRY_FLAGS=""
if [ "${DRY:-0}" = "1" ]; then
  DRY_FLAGS="-C0 -R0"
fi

python3 -I "$SCRIPT_DIR/make_note_midi.py" "$TMP_MID" "$PROGRAM" "$VELOCITY" "$BEATS" "${NOTES[@]}"
fluidsynth -ni $DRY_FLAGS -F "$TMP_WAV" -r 44100 -q "$SF2" "$TMP_MID"
sox "$TMP_WAV" "$OUT" gain -n -8 trim 0 2.2 fade 0 2.2 0.2

rm -f "$TMP_MID" "$TMP_WAV"
