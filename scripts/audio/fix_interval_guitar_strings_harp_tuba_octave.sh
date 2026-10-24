#!/bin/bash
# Re-renders Guitar, Jazz Guitar, Bass Guitar, Strings, Harp (one octave up,
# same fix as scripts/audio/fix_interval_ep_organ_octave.sh) and Tuba (two
# octaves up — confirmed via scripts/audio/detect_pitch.py / narrow-band
# autocorrelation that Tuba was rooted an octave BELOW the others, at MIDI 24
# instead of 36, so +24 brings it to the same MIDI 48 root as everyone else's
# +12) for the 4 "Intervals" quizzes (Melodic/Harmonic Diatonic/Non-diatonic).
#
# Bass Guitar is normally left unshifted elsewhere (Basic Triads, Dyad
# Melodies) since its low range is usually idiomatic — explicitly overridden
# here per direct instruction for the Intervals quizzes specifically.
#
# Paths follow the existing convention:
#   reference-intervals/{melodic,harmonic}-{diatonic,non-diatonic}/{instrument}/{slug}.wav
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
STORAGE="$SCRIPT_DIR/../../storage/app/public/reference-intervals"

VELOCITY=100
BEATS_NOTE=3.83   # melodic: matches old ~3.64s total (2 notes) at 120bpm
BEATS_CHORD=3.2   # harmonic: "standard note hold" per render_note.sh

# slug => semitone offset above ROOT
DIATONIC="major-2nd:2 major-3rd:4 perfect-4th:5 perfect-5th:7 major-6th:9 major-7th:11 octave:12"
NON_DIATONIC="minor-2nd:1 minor-3rd:3 tri-tone:6 minor-6th:8 minor-7th:10"

program_for() {
  case "$1" in
    guitar) echo 25 ;;
    jazz-guitar) echo 26 ;;
    bass-guitar) echo 33 ;;
    strings) echo 48 ;;
    harp) echo 46 ;;
    tuba) echo 58 ;;
  esac
}

root_for() {
  case "$1" in
    tuba) echo 48 ;;   # was 24, +24 (two octaves)
    *) echo 48 ;;       # was 36, +12 (one octave)
  esac
}

render_set() {
  local kind="$1" quality="$2" pairs="$3"
  for sound in guitar jazz-guitar bass-guitar strings harp tuba; do
    program="$(program_for "$sound")"
    root="$(root_for "$sound")"
    outdir="$STORAGE/${kind}-${quality}/${sound}"
    mkdir -p "$outdir"
    for entry in $pairs; do
      slug="${entry%%:*}"
      offset="${entry##*:}"
      upper=$((root + offset))
      out="$outdir/${slug}.wav"
      if [ "$kind" = "melodic" ]; then
        bash -c "\"$SCRIPT_DIR/render_sequence.sh\" \"$out\" $program $VELOCITY $BEATS_NOTE $root $upper"
      else
        bash -c "\"$SCRIPT_DIR/render_note.sh\" \"$out\" $program $VELOCITY $BEATS_CHORD $root $upper"
      fi
      echo "rendered $out"
    done
  done
}

render_set melodic diatonic "$DIATONIC"
render_set melodic non-diatonic "$NON_DIATONIC"
render_set harmonic diatonic "$DIATONIC"
render_set harmonic non-diatonic "$NON_DIATONIC"

echo "Done."
