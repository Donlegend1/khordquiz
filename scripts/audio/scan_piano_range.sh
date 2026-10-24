#!/bin/bash
# One-off: scans every chord-quiz voicing generator with base=48 (Piano's
# register) to find the true min/max MIDI note ever rendered for Piano
# across the whole "Degree Chords" series, so we know the real octave range
# needed before recording replacement samples.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

ALL_NOTES="/tmp/piano_range_notes.txt"
> "$ALL_NOTES"

run() {
  echo "running: $*" >&2
  php "$@" >> "$ALL_NOTES" 2>/dev/null || echo "  (failed: $*)" >&2
}

run gen_basic_triads_new_instruments_voicings.php 1 48
run gen_basic_triads_new_instruments_voicings.php 2 48
run gen_basic_triads_new_instruments_voicings.php 3 48
run gen_basic_triads_new_instruments_voicings.php 4 48
run gen_basic_triads_new_instruments_voicings.php 5 48
run gen_basic_triads_new_instruments_voicings.php 6 48
run gen_basic_triads_new_instruments_voicings.php 7 48
run gen_basic_triads_new_instruments_voicings.php 8 48

run gen_add9_voicings.php 1 48
run gen_add9_voicings.php 2 48
run gen_add9_voicings.php 3 48
run gen_add9_voicings.php 4 48
run gen_add9_voicings.php 5 48
run gen_add9_voicings.php 6 48
run gen_add9_voicings.php 7 48
run gen_add9_voicings.php 8 48
run gen_add9_level8_voicings.php 48

run gen_seventh_voicings.php 1 48
run gen_seventh_voicings.php 2 48
run gen_seventh_voicings.php 3 48
run gen_seventh_voicings.php 4 48
run gen_seventh_voicings.php 5 48
run gen_seventh_voicings.php 6 48
run gen_seventh_voicings.php 7 48
run gen_seventh_voicings.php 8 48

run gen_secondary_seventh_voicings.php 1 48
run gen_secondary_seventh_voicings.php 2 48
run gen_secondary_seventh_voicings.php 3 48
run gen_secondary_seventh_voicings.php 4 48
run gen_secondary_seventh_voicings.php 5 48
run gen_secondary_seventh_voicings.php 6 48
run gen_secondary_seventh_voicings.php 7 48
run gen_secondary_seventh_voicings.php 8 48
run gen_secondary_seventh_general_voicings.php 48

run gen_ninth_voicings.php 1 48
run gen_ninth_voicings.php 2 48
run gen_ninth_level3_voicings.php 48
run gen_ninth_level4_voicings.php 48
run gen_ninth_level5_voicings.php 48
run gen_ninth_level6_voicings.php 48

run gen_secondary_ninth_voicings.php 1 48
run gen_secondary_ninth_voicings.php 2 48
run gen_secondary_ninth_level3_voicings.php 48
run gen_secondary_ninth_level4_voicings.php 48
run gen_secondary_ninth_level5_voicings.php 48
run gen_secondary_ninth_level6_voicings.php 48
run gen_secondary_ninth_general_voicings.php 48

run gen_eleventh_level1_voicings.php 48
run gen_eleventh_level2_voicings.php 48
run gen_eleventh_level3_voicings.php 48
run gen_eleventh_level4_voicings.php 48
run gen_eleventh_level5_voicings.php 48
run gen_eleventh_level6_voicings.php 48

run gen_secondary_eleventh_voicings.php 1 48
run gen_secondary_eleventh_voicings.php 2 48
run gen_secondary_eleventh_voicings.php 3 48
run gen_secondary_eleventh_voicings.php 4 48
run gen_secondary_eleventh_voicings.php 5 48
run gen_secondary_eleventh_voicings.php 6 48

run gen_thirteenth_level1_voicings.php 48
run gen_thirteenth_level2_voicings.php 48
run gen_thirteenth_level3_voicings.php 48
run gen_thirteenth_level4_voicings.php 48
run gen_thirteenth_level5_voicings.php 48
run gen_thirteenth_level6_voicings.php 48

run gen_chord_tone_recognition1_voicings.php 48
run gen_chord_tone_recognition2_voicings.php 48

echo "--- scanning complete, computing range ---" >&2

python3 -c "
notes = []
for line in open('$ALL_NOTES'):
    for field in line.rstrip('\n').split('\t'):
        parts = field.split(',')
        if all(p.strip().lstrip('-').isdigit() for p in parts if p.strip()):
            for p in parts:
                p = p.strip()
                if p:
                    notes.append(int(p))
notes = [n for n in notes if 0 <= n <= 127]
lo, hi = min(notes), max(notes)
names = ['C','C#','D','D#','E','F','F#','G','G#','A','A#','B']
def name(n):
    return f'{names[n % 12]}{n // 12 - 1}'
print(f'Lowest MIDI note:  {lo} ({name(lo)})')
print(f'Highest MIDI note: {hi} ({name(hi)})')
print(f'Total notes scanned: {len(notes)}')
"
