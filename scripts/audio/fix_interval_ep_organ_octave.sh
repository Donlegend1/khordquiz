#!/bin/bash
# Re-renders EP and Organ audio for the 4 "Intervals" quizzes (Melodic Diatonic,
# Melodic Non-diatonic, Harmonic Diatonic, Harmonic Non-diatonic) one octave
# higher. Confirmed via scripts/audio/detect_pitch.py (autocorrelation on the
# existing files) that both EP and Organ were rooted at MIDI 36 (C2) — genuinely
# too low, matching the user's "too deep" report. New root: MIDI 48 (C3).
#
# Melodic = two notes one after another (render_sequence.sh). Harmonic = both
# notes together as a held chord (render_note.sh, confirmed by the existing
# harmonic files' 2.2s duration matching render_note.sh's hard trim, vs.
# melodic files' untrimmed ~3.6s matching render_sequence.sh).
#
# Paths follow the existing convention:
#   reference-intervals/{melodic,harmonic}-{diatonic,non-diatonic}/{ep,organ}/{slug}.wav
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
STORAGE="$SCRIPT_DIR/../../storage/app/public/reference-intervals"

ROOT=48 # C3 (was 36/C2)
VELOCITY=100
BEATS_NOTE=3.83   # melodic: matches old ~3.64s total (2 notes) at 120bpm
BEATS_CHORD=3.2   # harmonic: "standard note hold" per render_note.sh

# slug => semitone offset above ROOT
DIATONIC="major-2nd:2 major-3rd:4 perfect-4th:5 perfect-5th:7 major-6th:9 major-7th:11 octave:12"
NON_DIATONIC="minor-2nd:1 minor-3rd:3 tri-tone:6 minor-6th:8 minor-7th:10"

program_for() {
  case "$1" in
    ep) echo 4 ;;
    organ) echo 16 ;;
  esac
}

render_set() {
  local kind="$1" quality="$2" pairs="$3"
  for sound in ep organ; do
    program="$(program_for "$sound")"
    outdir="$STORAGE/${kind}-${quality}/${sound}"
    mkdir -p "$outdir"
    for entry in $pairs; do
      slug="${entry%%:*}"
      offset="${entry##*:}"
      upper=$((ROOT + offset))
      out="$outdir/${slug}.wav"
      if [ "$kind" = "melodic" ]; then
        bash -c "\"$SCRIPT_DIR/render_sequence.sh\" \"$out\" $program $VELOCITY $BEATS_NOTE $ROOT $upper"
      else
        bash -c "\"$SCRIPT_DIR/render_note.sh\" \"$out\" $program $VELOCITY $BEATS_CHORD $ROOT $upper"
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
