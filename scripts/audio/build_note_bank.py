#!/usr/bin/env python3
"""
Builds a do/re/mi{-cN} + chromatic-sharp note bank for one instrument from a
single chromatic-sweep raw recording, matching the convention established for
Acoustic/Electric Guitar: bare filename = octave 3 (the quiz's reference
octave), every other octave suffixed "-c{octave}"; the 5 chromatic sharps
(C#/D#/F#/G#/A#) are stored once at whichever octave the recording's sweep
happens to cover them at, not per-octave (consumers pitch-shift via sox when
a different octave's sharp is needed, same as the rest of the project).

Usage:
    build_note_bank.py <raw_wav> <instrument_slug> <low_midi> <high_midi> [--start-midi N]

<low_midi>/<high_midi> are the usable range to keep (MIDI numbers, e.g. G2=43,
F5=77). The recording is assumed to be one continuous monotonic ascending
chromatic sweep; --start-midi pins the first clean segment's pitch instead of
trusting pitch-detection on it (autocorrelation is unreliable at the very low
end — pass this when you already know the recording's starting note).

Writes directly into storage/app/public/notes/<instrument_slug>/, overwriting
any existing files with the same name.
"""
import sys
import subprocess
import shutil
import tempfile
import os
import wave
import audioop
import math
import argparse

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
REPO_ROOT = os.path.dirname(os.path.dirname(SCRIPT_DIR))
NOTES_DIR = os.path.join(REPO_ROOT, 'storage', 'app', 'public', 'notes')

OFFSETS = {0: 'do', 2: 're', 4: 'mi', 5: 'fa', 7: 'so', 9: 'la', 11: 'ti'}
CHROMATIC = {1: 'csharp', 3: 'dsharp', 6: 'fsharp', 8: 'gsharp', 10: 'asharp'}

# Find the Note's chromatic-letter scheme is pitch-identical to the solfège
# scheme at octave 3 — same files, different names, no separate audio needed
# (confirmed: both schemes already share the same literal sharp filenames).
LETTER_EQUIVALENTS = {'do': 'c', 're': 'd', 'mi': 'e', 'fa': 'f', 'so': 'g', 'la': 'a', 'ti': 'b'}


def midi_to_filename(midi):
    octave = midi // 12 - 1
    remainder = midi - (octave + 1) * 12
    if remainder in OFFSETS:
        suffix = '' if octave == 3 else f'-c{octave}'
        return f'{OFFSETS[remainder]}{suffix}.wav'
    return f'{CHROMATIC[remainder]}.wav'


def detect_pitch(path, start=0.0, duration=1.0):
    result = subprocess.run(
        ['python3', os.path.join(SCRIPT_DIR, 'detect_pitch.py'), path, str(start), str(duration)],
        capture_output=True, text=True,
    )
    for token in result.stdout.split():
        if token.startswith('midi='):
            return float(token.split('=')[1].split('(')[0])
    return None


def split_by_silence(raw_wav, out_dir, min_silence='1.0', threshold='0.5%', fallback=False, min_duration=1.0):
    sixteen = os.path.join(out_dir, 'norm16.wav')
    subprocess.run(['sox', raw_wav, '-e', 'signed-integer', '-b', '16', sixteen], check=True)
    pattern = os.path.join(out_dir, 'note.wav')
    silence_spec = ['1', '0.3', '1%'] if fallback else ['1', min_silence, threshold]
    subprocess.run(
        ['sox', sixteen, pattern, 'silence', '1', '0.05', threshold, *silence_spec, ':', 'newfile', ':', 'restart'],
        check=True, capture_output=True,
    )
    segments = sorted(f for f in os.listdir(out_dir) if f.startswith('note') and f.endswith('.wav'))
    results = []
    for name in segments:
        path = os.path.join(out_dir, name)
        w = wave.open(path, 'rb')
        duration = w.getnframes() / w.getframerate()
        w.close()
        if duration >= min_duration:
            results.append(path)
    return results


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('raw_wav')
    parser.add_argument('slug')
    parser.add_argument('low_midi', type=int)
    parser.add_argument('high_midi', type=int)
    parser.add_argument('--start-midi', type=int, default=None)
    parser.add_argument('--fallback-split', action='store_true', help='Use the shorter min-silence split for high-sustain instruments')
    parser.add_argument('--skip-segments', type=int, default=0, help='Discard this many leading segments (silent/noise lead-in before the real sweep starts)')
    parser.add_argument('--max-segments', type=int, default=None, help='Only use this many segments after the skip (discard a repeated second pass at the tail)')
    parser.add_argument('--clean', action='store_true', help='Delete every existing file in the instrument folder first, instead of only overwriting the ones this run touches')
    parser.add_argument('--min-duration', type=float, default=1.0, help='Discard split segments shorter than this many seconds — lower for fast-decaying/percussive instruments (e.g. 0.3 for Marimba)')
    args = parser.parse_args()

    with tempfile.TemporaryDirectory() as tmp:
        segments = split_by_silence(args.raw_wav, tmp, fallback=args.fallback_split, min_duration=args.min_duration)
        print(f'Split into {len(segments)} segments >= 1.0s')
        segments = segments[args.skip_segments:]
        if args.max_segments is not None:
            segments = segments[:args.max_segments]

        if args.start_midi is not None:
            start_midi = args.start_midi
        else:
            first_pitch = detect_pitch(segments[0], 0.3, 1.0)
            if first_pitch is None:
                print('Could not detect starting pitch — pass --start-midi explicitly.')
                sys.exit(1)
            start_midi = round(first_pitch)
            print(f'Detected starting MIDI: {start_midi} (from {first_pitch:.2f})')

        out_dir = os.path.join(NOTES_DIR, args.slug)
        if args.clean and os.path.isdir(out_dir):
            shutil.rmtree(out_dir)
        os.makedirs(out_dir, exist_ok=True)

        in_range = {}
        for i, seg in enumerate(segments):
            midi = start_midi + i
            if args.low_midi <= midi <= args.high_midi:
                in_range[midi] = seg

        written = []
        for midi, seg in in_range.items():
            octave = midi // 12 - 1
            remainder = midi - (octave + 1) * 12
            if remainder in CHROMATIC:
                continue  # sharps handled separately below, one instance only
            filename = midi_to_filename(midi)
            dest = os.path.join(out_dir, filename)
            shutil.copy(seg, dest)
            written.append((midi, filename))

            # Find the Note's chromatic letter scheme is its OWN octave
            # convention, separate from the solfège scheme's (bare filename
            # = octave 3) — confirmed against Piano's existing, untouched
            # letter files: c.wav through b.wav run C4->B4, octave 4, not 3.
            # Sharps are already captured preferring octave 4 (see below),
            # so octave 4 is the one that keeps this instrument's own
            # chromatic run internally consistent (previously copied from
            # octave 3, which put e.g. "d.wav" a full octave+ below
            # "dsharp.wav" — audibly wrong, caught by ear on Acoustic
            # Guitar's Find the Note).
            if octave == 4 and remainder in OFFSETS:
                letter_dest = os.path.join(out_dir, f'{LETTER_EQUIVALENTS[OFFSETS[remainder]]}.wav')
                shutil.copy(seg, letter_dest)
                written.append((midi, os.path.basename(letter_dest)))

        # Sharps: one stored instance each, preferring octave 4 (matches the
        # established CHROMATIC = {1: ('csharp', 61), ...} convention used
        # elsewhere in scripts/audio) — falls back to whichever in-range
        # octave is closest to 4 if 4 itself wasn't recorded/kept.
        by_remainder = {}
        for midi in in_range:
            remainder = midi - (midi // 12) * 12
            by_remainder.setdefault(remainder, []).append(midi)
        for remainder, name in CHROMATIC.items():
            candidates = by_remainder.get(remainder, [])
            if not candidates:
                continue
            chosen = min(candidates, key=lambda m: abs((m // 12 - 1) - 4))
            dest = os.path.join(out_dir, f'{name}.wav')
            shutil.copy(in_range[chosen], dest)
            written.append((chosen, f'{name}.wav'))

        print(f'Wrote {len(written)} files to {out_dir}')
        for midi, filename in written:
            print(f'  MIDI {midi}: {filename}')


if __name__ == '__main__':
    main()
