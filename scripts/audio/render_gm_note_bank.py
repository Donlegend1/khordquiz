#!/usr/bin/env python3
"""
Renders a do/re/mi{-cN} + chromatic-sharp note bank for a GM-soundfont
instrument across a MIDI range — the synthesized equivalent of
build_note_bank.py (same filename convention, reused directly), for
instruments where the user decided on a GM sound rather than a real
recording (Violin, Cello, Tenor Saxophone, Trombone, Tuba, Trumpet,
Clarinet, Bassoon).

Every range here is extended down to at least MIDI 48 (C3) even where a
real instrument couldn't physically play that low — matches the existing
precedent for Bass Guitar (see QuizController's KEYBOARD_NOTE_SUFFIXES
comment: its bare files stay at the quiz's C3 octave even though a real
bass doesn't naturally sit there), since RTP1/intervals rely on every
instrument having that octave's bare files.

Usage: render_gm_note_bank.py <slug> <gm_program_0indexed> <low_midi> <high_midi> [--soundfont PATH] [--clean]
"""
import sys
import os
import subprocess
import argparse
import shutil

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, SCRIPT_DIR)
from build_note_bank import midi_to_filename, LETTER_EQUIVALENTS, OFFSETS, CHROMATIC, NOTES_DIR  # noqa: E402

DEFAULT_SF2 = '/private/tmp/claude-501/-Users-khord-Documents-GitHub-Khordquizwebsite/024aab61-577b-471a-93e1-53dcbc70aa8d/scratchpad/soundfonts/FluidR3_GM.sf2'


def render_note(out_path, program, midi, soundfont):
    env = {**os.environ, 'GENERALUSER_SF2': soundfont}
    subprocess.run(
        ['bash', os.path.join(SCRIPT_DIR, 'render_note.sh'), out_path, str(program), '100', '3.2', str(midi)],
        check=True, capture_output=True, env=env,
    )


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('slug')
    parser.add_argument('program', type=int, help='GM program number, 0-indexed (e.g. 40 for Violin)')
    parser.add_argument('low_midi', type=int)
    parser.add_argument('high_midi', type=int)
    parser.add_argument('--soundfont', default=DEFAULT_SF2)
    parser.add_argument('--clean', action='store_true')
    args = parser.parse_args()

    out_dir = os.path.join(NOTES_DIR, args.slug)
    if args.clean and os.path.isdir(out_dir):
        shutil.rmtree(out_dir)
    os.makedirs(out_dir, exist_ok=True)

    written = []
    for midi in range(args.low_midi, args.high_midi + 1):
        octave = midi // 12 - 1
        remainder = midi - (octave + 1) * 12
        if remainder not in OFFSETS:
            continue
        filename = midi_to_filename(midi)
        dest = os.path.join(out_dir, filename)
        render_note(dest, args.program, midi, args.soundfont)
        written.append((midi, filename))

        # Find the Note's chromatic letter scheme uses octave 4, not the
        # solfège scheme's octave 3 — see build_note_bank.py's matching
        # comment for how this was confirmed (against Piano's own existing,
        # correct letter files).
        if octave == 4:
            letter_dest = os.path.join(out_dir, f'{LETTER_EQUIVALENTS[OFFSETS[remainder]]}.wav')
            render_note(letter_dest, args.program, midi, args.soundfont)
            written.append((midi, os.path.basename(letter_dest)))

    for remainder, name in CHROMATIC.items():
        candidates = [m for m in range(args.low_midi, args.high_midi + 1) if (m - (m // 12) * 12) == remainder]
        if not candidates:
            continue
        chosen = min(candidates, key=lambda m: abs((m // 12 - 1) - 4))
        dest = os.path.join(out_dir, f'{name}.wav')
        render_note(dest, args.program, chosen, args.soundfont)
        written.append((chosen, f'{name}.wav'))

    print(f'Wrote {len(written)} files to {out_dir}')
    for midi, filename in written:
        print(f'  MIDI {midi}: {filename}')


if __name__ == '__main__':
    main()
