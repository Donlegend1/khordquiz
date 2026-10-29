#!/usr/bin/env python3
"""Independently check interval labels, timing, sample pitches and rendered audio.

A successful audit writes a hash receipt required by build_interval_live_mix.php --apply.
Only the Python standard library, ffmpeg, sox and soxi are required.
"""
import hashlib
import json
import math
import re
import struct
import subprocess
import sys
from pathlib import Path

PUBLIC = Path(__file__).resolve().parents[2] / 'storage/app/public'
RATE = 8000
DISTANCES = {'Minor 2nd': 1, 'Major 2nd': 2, 'Minor 3rd': 3, 'Major 3rd': 4,
             'Perfect 4th': 5, 'Tri Tone': 6, 'Perfect 5th': 7, 'Minor 6th': 8,
             'Major 6th': 9, 'Minor 7th': 10, 'Major 7th': 11, 'Octave': 12}


def digest(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def pcm(path, duration, rate=RATE):
    raw = subprocess.check_output(['ffmpeg', '-v', 'error', '-i', str(path), '-t', str(duration),
                                   '-ac', '1', '-ar', str(rate), '-f', 'f32le', '-'])
    return struct.unpack(f'<{len(raw) // 4}f', raw)


def cosine(first, second):
    assert len(first) == len(second) and first
    energy_a = sum(value * value for value in first)
    energy_b = sum(value * value for value in second)
    if min(energy_a, energy_b) < 1e-10:
        raise ValueError('Silent audio window')
    return sum(a * b for a, b in zip(first, second)) / math.sqrt(energy_a * energy_b)


def pitch_estimate(samples, expected, start, rate=RATE):
    frequency = 440 * 2 ** ((expected - 69) / 12)
    low = max(3, int(rate / (frequency * 2.1)))
    high = int(rate / (frequency / 2.1))
    offset = int(start * rate)
    count = rate // 10
    window = samples[offset:offset + count + high]
    if len(window) < count + high:
        return None
    scores = {}
    for lag in range(low, high + 1):
        numerator = sum((window[i] - window[i + lag]) ** 2 for i in range(count))
        denominator = sum(window[i] ** 2 + window[i + lag] ** 2 for i in range(count))
        scores[lag] = numerator / max(denominator, 1e-20)
    minima = [lag for lag in range(low + 1, high)
              if scores[lag] < scores[lag - 1] and scores[lag] <= scores[lag + 1]]
    if not minima:
        return None
    # Compare sub-sample minima: integer lag error on bright instruments can
    # otherwise make a doubled period look better than the true fundamental.
    refined = {}
    for lag in minima:
        a, b, c = scores[lag - 1], scores[lag], scores[lag + 1]
        refined[lag] = max(0, b - (a - c) ** 2 / (8 * (a - 2 * b + c)))
    best = min(refined.values())
    candidates = [lag for lag in minima if refined[lag] <= min(0.25, best + 0.035)]
    if not candidates:
        return None
    lag = min(candidates)
    a, b, c = scores[lag - 1], scores[lag], scores[lag + 1]
    period = lag + (a - c) / (2 * (a - 2 * b + c))
    return 69 + 12 * math.log2((rate / period) / 440)


def main():
    manifest_path = Path(sys.argv[1])
    manifest = json.loads(manifest_path.read_text())
    sources = {}
    for entry in manifest['files']:
        for note in entry['notes']:
            previous = sources.setdefault(note['path'], {'midi': note['midi']})
            if previous['midi'] != note['midi']:
                raise ValueError('One sample assigned conflicting pitches')
    issues = []
    for index, (relative, source) in enumerate(sources.items(), 1):
        path = PUBLIC / relative
        source['pcm'] = pcm(path, 0.75)
        estimates = [pitch_estimate(source['pcm'], source['midi'], start) for start in [0.12, 0.30, 0.45]]
        valid = [estimate for estimate in estimates if estimate is not None]
        closest = min(valid, key=lambda value: abs(value - source['midi'])) if valid else None
        if closest is None or abs(closest - source['midi']) > 0.45:
            # Bright brass/reed harmonics need finer lag resolution than the
            # 8 kHz waveform-comparison stream; verify again at 24 kHz.
            precise = pcm(path, 0.75, 24000)
            estimates = [pitch_estimate(precise, source['midi'], start, 24000) for start in [0.12, 0.30, 0.45]]
            valid = [estimate for estimate in estimates if estimate is not None]
            closest = min(valid, key=lambda value: abs(value - source['midi'])) if valid else None
        source['estimated_midi'] = closest
        if closest is None or abs(closest - source['midi']) > 0.45:
            issues.append({'kind': 'source_pitch', 'path': relative, 'expected_midi': source['midi'], 'estimates': estimates})
        if index % 50 == 0 or index == len(sources):
            print(f'Checked source pitches {index}/{len(sources)}', flush=True)

    hashes = {}
    minimum_correlation = 1.0
    for index, entry in enumerate(manifest['files'], 1):
        path = PUBLIC / entry['output_path']
        first, second = entry['midi']
        if second - first != DISTANCES[entry['answer']]:
            raise ValueError('Wrong interval distance: ' + entry['output_path'])
        encoded = re.search(r'/(\d+)-(\d+)\.wav$', entry['output_path'])
        if not encoded or [int(encoded[1]), int(encoded[2])] != entry['midi']:
            raise ValueError('Filename and pitches disagree')
        offsets = [note['offset'] for note in entry['notes']]
        if offsets != ([0, 0.75] if entry['timing'] == 'melodic' else [0, 0]):
            raise ValueError('Wrong note timing')
        output = pcm(path, 1.2)
        note_sources = [sources[note['path']]['pcm'] for note in entry['notes']]
        if entry['timing'] == 'melodic':
            correlations = [cosine(source[800:2800], output[int(offset * RATE) + 800:int(offset * RATE) + 2800])
                            for source, offset in zip(note_sources, offsets)]
        else:
            expected = [a + b for a, b in zip(note_sources[0][800:2800], note_sources[1][800:2800])]
            correlations = [cosine(expected, output[800:2800])]
        minimum_correlation = min(minimum_correlation, *correlations)
        if min(correlations) < 0.995:
            issues.append({'kind': 'render_mismatch', 'path': entry['output_path'], 'correlations': correlations})
        stats = subprocess.run(['sox', str(path), '-n', 'stats'], capture_output=True, text=True, check=True).stderr
        peak = float(re.search(r'Pk lev dB\s+([-\d.]+)', stats)[1])
        if not -8.1 <= peak <= -7.9:
            raise ValueError('Unexpected render peak: ' + entry['output_path'])
        hashes[entry['output_path']] = digest(path)
        if index % 50 == 0 or index == len(manifest['files']):
            print(f'Checked interval audio {index}/{len(manifest["files"])}', flush=True)

    report = {'source_count': len(sources), 'file_count': len(hashes),
              'minimum_audio_correlation': minimum_correlation, 'issues': issues}
    Path(str(manifest_path) + '.audit.json').write_text(json.dumps(report, indent=2))
    if issues:
        print(json.dumps(issues[:20], indent=2))
        raise SystemExit(f'Audit needs review: {len(issues)} findings. No attachment receipt written.')
    receipt = {'manifest_sha256': digest(manifest_path), 'files': hashes,
               'sources': {relative: digest(PUBLIC / relative) for relative in sources}, **report}
    Path(str(manifest_path) + '.verified.json').write_text(json.dumps(receipt, indent=2))
    print(f'PASS: {len(hashes)} interval files; {len(sources)} source pitches; minimum audio correlation {minimum_correlation:.6f}.')


if __name__ == '__main__':
    main()
