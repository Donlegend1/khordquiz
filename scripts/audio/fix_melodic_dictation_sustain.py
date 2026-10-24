#!/usr/bin/env python3
"""Rebuilds the 5 piano-family melodic-dictation quizzes (3-Note Stepwise,
3-Note with Skips, 5-Note Melodies, Dual-tonic melodies, Dyad Melodies) for
Piano, EP, Upright Piano and Organ so each note's natural decay rings through
into the next note's attack — overlaid/mixed, not trimmed-and-concatenated
with a silence gap — matching how Guitar's GM-rendered notes already overlap.

Source of truth for each question's exact MIDI notes: the manifest JSON files
already committed under scripts/audio/ (e.g. three_note_stepwise_piano_manifest.json).
Raw per-note samples (one WAV per solfege degree x octave, ~3.2s each, full
natural decay) live in storage/app/public/notes/<instrument>/ and are shared
identically across Piano/EP/Upright Piano/Organ by filename.

Usage: fix_melodic_dictation_sustain.py
"""
import json
import subprocess
import tempfile
import os

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
NOTES_DIR = os.path.join(ROOT, 'storage/app/public/notes')
OUT_ROOT = os.path.join(ROOT, 'storage/app/public/melodic-dictation')
MANIFEST_DIR = os.path.join(ROOT, 'scripts/audio')

INSTRUMENTS = ['piano', 'ep', 'upright-piano', 'organ']
STEP = 0.75  # seconds between note onsets, matching guitar's measured ~0.73-0.74s grid

# (manifest filename, quiz output folder, has_voicings)
QUIZZES = [
    ('three_note_stepwise_piano_manifest.json', '3-note-stepwise', False),
    ('three_note_skips_piano_manifest.json', '3-note-skips', False),
    ('five_note_melodies_piano_manifest.json', '5-note-melodies', False),
    ('dual_tonic_melodies_piano_manifest.json', 'dual-tonic-melodies', False),
    ('dyad_melodies_piano_manifest.json', 'dyad-melodies', True),
]

DEGREE_SLUGS = ['do', 're', 'mi', 'fa', 'so', 'la', 'ti']
OFFSETS = {0: 'do', 2: 're', 4: 'mi', 5: 'fa', 7: 'so', 9: 'la', 11: 'ti'}


def sample_path(instrument: str, midi: int) -> str:
    octave = midi // 12 - 1
    remainder = midi - (octave + 1) * 12
    if remainder not in OFFSETS:
        raise ValueError(f'MIDI {midi} is not a diatonic degree (remainder {remainder})')
    degree = OFFSETS[remainder]
    suffix = '' if octave == 3 else f'-c{octave}'
    path = os.path.join(NOTES_DIR, instrument, f'{degree}{suffix}.wav')
    if not os.path.exists(path):
        raise FileNotFoundError(path)
    return path


def run(cmd):
    subprocess.run(cmd, check=True, stdout=subprocess.DEVNULL, stderr=subprocess.PIPE)


def build_step_source(instrument: str, step, tmpdir: str, index: int) -> str:
    """step is either an int MIDI note, or a list of MIDI notes (dyad) to mix together first."""
    if isinstance(step, list):
        parts = [sample_path(instrument, n) for n in step]
        out = os.path.join(tmpdir, f'step{index}_dyad.wav')
        run(['sox', '-m', *parts, out, 'gain', '-n', '-3'])
        return out
    return sample_path(instrument, step)


def build_sequence(instrument: str, steps, out_path: str):
    with tempfile.TemporaryDirectory() as tmpdir:
        padded = []
        for i, step in enumerate(steps):
            src = build_step_source(instrument, step, tmpdir, i)
            dst = os.path.join(tmpdir, f'padded{i}.wav')
            run(['sox', src, dst, 'pad', f'{i * STEP:.3f}', '0'])
            padded.append(dst)
        run(['sox', '-m', *padded, out_path, 'gain', '-n', '-8'])


def main():
    total = 0
    failures = []
    for manifest_name, quiz_slug, has_voicings in QUIZZES:
        manifest_path = os.path.join(MANIFEST_DIR, manifest_name)
        rows = json.load(open(manifest_path))
        for instrument in INSTRUMENTS:
            out_dir = os.path.join(OUT_ROOT, quiz_slug, instrument)
            os.makedirs(out_dir, exist_ok=True)
            for row in rows:
                steps = row['voicings'] if has_voicings else row['notes']
                out_path = os.path.join(out_dir, f"{row['file_slug']}.wav")
                try:
                    build_sequence(instrument, steps, out_path)
                    total += 1
                except Exception as exc:
                    failures.append((quiz_slug, instrument, row['file_slug'], str(exc)))
        print(f'{quiz_slug}: done ({len(rows)} rows x {len(INSTRUMENTS)} instruments)')

    print(f'Total files written: {total}')
    if failures:
        print(f'{len(failures)} FAILURES:')
        for f in failures[:20]:
            print(' ', f)


if __name__ == '__main__':
    main()
