#!/bin/bash
# Re-renders every Bass Guitar chord file in Basic Triads (Levels 1-8) fresh
# from MIDI via FluidSynth — replacing the sox-pitch-shifted versions, which
# had a warbly/phasy artifact on chords (sox's `pitch` effect is a time-
# domain algorithm tuned for monophonic material). Notes come from
# gen_basic_triads_bass_guitar_voicings.py, which mirrors each level's own
# build script exactly and uses Piano's register (confirmed by Level 5's own
# comment: Bass Guitar was originally rendered a full octave below Piano;
# this brings it back up to match, matching the user's earlier "+1 octave"
# request without the pitch-shift artifact).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
STORAGE="$(cd "$SCRIPT_DIR/../.." && pwd)/storage/app/public"
PROGRAM=33
VELOCITY=100
BEATS=3.2

count=0
while IFS=$'\t' read -r path notes; do
  out="$STORAGE/$path"
  mkdir -p "$(dirname "$out")"
  IFS=',' read -ra note_arr <<< "$notes"
  bash -c "\"$SCRIPT_DIR/render_note.sh\" \"$out\" $PROGRAM $VELOCITY $BEATS ${note_arr[*]}"
  count=$((count + 1))
done < <(python3 "$SCRIPT_DIR/gen_basic_triads_bass_guitar_voicings.py")

echo "Rendered ${count} Bass Guitar files."
