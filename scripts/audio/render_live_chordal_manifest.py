#!/usr/bin/env python3
"""Render resolved note groups, preserving the live engine's timing and fades.

Usage: render_live_chordal_manifest.py <manifest.json>
The manifest comes from raise_organ_chordal_melodies.php.
"""
import json
import subprocess
import sys
import tempfile
from collections import defaultdict
from pathlib import Path

PUBLIC = Path(__file__).resolve().parents[2] / 'storage/app/public'
FADE_SECONDS = 0.015


def run(command):
    result = subprocess.run(command, capture_output=True, text=True)
    if result.returncode:
        raise RuntimeError(result.stderr)


def public_path(relative):
    path = (PUBLIC / relative).resolve()
    path.relative_to(PUBLIC.resolve())
    return path


def render(entry):
    chords = defaultdict(list)
    for note in entry['notes']:
        source = public_path(note['path'])
        if not source.is_file():
            raise FileNotFoundError(source)
        chords[float(note['offset'])].append(source)
    if not chords:
        raise ValueError('A chordal sequence must contain notes')

    output = public_path(entry['output_path'])
    output.parent.mkdir(parents=True, exist_ok=True)
    with tempfile.TemporaryDirectory(prefix='organ-chordal-') as directory:
        temporary = Path(directory)
        normalized = {}
        segments = []
        offsets = sorted(chords)
        for index, offset in enumerate(offsets):
            parts = []
            for source in chords[offset]:
                if source not in normalized:
                    destination = temporary / f'note-{len(normalized)}.wav'
                    run(['sox', str(source), '-r', '48000', '-b', '24', str(destination)])
                    normalized[source] = destination
                parts.append(str(normalized[source]))
            mixed = temporary / f'chord-{index}.wav'
            if len(parts) == 1:
                mixed = Path(parts[0])
            else:
                run(['sox', '-m', *parts, str(mixed), 'gain', '-n', '-3'])
            segment = temporary / f'segment-{index}.wav'
            effects = []
            if index < len(offsets) - 1:
                duration = offsets[index + 1] - offset
                effects = ['trim', '0', str(duration), 'fade', '0', str(duration), str(FADE_SECONDS)]
            run(['sox', str(mixed), str(segment), *effects, 'pad', str(offset), '0'])
            segments.append(str(segment))
        rendered = temporary / 'rendered.wav'
        mix_option = ['-m'] if len(segments) > 1 else []
        run(['sox', *mix_option, *segments, '-b', '24', str(rendered), 'gain', '-n', '-8'])
        # Write the complete render only; source recordings are never modified.
        output.write_bytes(rendered.read_bytes())


def main():
    with open(sys.argv[1]) as source:
        manifest = json.load(source)
    total = len(manifest['files'])
    for index, entry in enumerate(manifest['files'], 1):
        render(entry)
        if index % 25 == 0 or index == total:
            print(f'Rendered {index}/{total} files', flush=True)


if __name__ == '__main__':
    main()
