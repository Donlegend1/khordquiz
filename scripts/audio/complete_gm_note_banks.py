#!/usr/bin/env python3
"""Fill verified GM note-bank gaps without replacing existing source recordings.

The plan is a JSON object keyed by instrument slug, containing low/high MIDI
limits and sources mapping each available MIDI number to its current sample path.
A completed bank uses explicit midi-N.wav filenames, avoiding legacy sharp-octave
collisions. The gm-bank.json marker activates it only after all files are ready.
"""
import argparse
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
from concurrent.futures import ThreadPoolExecutor

ROOT = Path(__file__).resolve().parents[2]
PROGRAMS = {'celesta': 8, 'vibraphone': 11, 'saxophone': 65, 'voice': 52}
FONTS = {'celesta': 'GeneralUser-1.471.sf2', 'vibraphone': 'GeneralUser-1.471.sf2',
         'saxophone': 'FluidR3_GM.sf2', 'voice': 'GeneralUser-1.471.sf2'}


def digest(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--plan', type=Path, required=True)
    parser.add_argument('--apply', action='store_true')
    args = parser.parse_args()
    plan = json.loads(args.plan.read_text())
    public = ROOT / 'storage/app/public'
    font_dir = ROOT / 'storage/app/private/soundfonts'
    tasks = []
    originals = {}
    reports = {}
    for slug, bank in plan.items():
        if slug not in PROGRAMS or not 0 <= bank['low'] <= bank['high'] <= 127:
            raise ValueError('Unexpected GM bank: ' + slug)
        font = font_dir / FONTS[slug]
        if not font.is_file():
            raise FileNotFoundError(font)
        sources = {int(midi): path for midi, path in bank['sources'].items()}
        reports[slug] = {'low': bank['low'], 'high': bank['high'], 'program': PROGRAMS[slug],
                         'soundfont': font.name, 'soundfont_sha256': digest(font),
                         'copied': [], 'generated': [], 'sources': sources, 'pitch_shifted_from': {}}
        marker = public / 'notes' / slug / 'gm-bank.json'
        if marker.is_file():
            previous = json.loads(marker.read_text())
            reports[slug]['pitch_shifted_from'] = previous.get('pitch_shifted_from', {})
        else:
            previous = {}
        for midi in range(bank['low'], bank['high'] + 1):
            dest = public / 'notes' / slug / f'midi-{midi}.wav'
            if midi in sources:
                src = public / sources[midi]
                if not src.is_file() or src.resolve().parent != (public / 'notes' / slug).resolve():
                    raise ValueError('Invalid existing source: ' + str(src))
                originals[str(src)] = digest(src)
                if dest.exists() and digest(dest) != digest(src):
                    raise ValueError('Refusing to replace a different sample: ' + str(dest))
                if args.apply and not dest.exists():
                    shutil.copyfile(src, dest)
                reports[slug]['copied'].append(midi)
            else:
                if args.apply and dest.exists():
                    expected = previous.get('sample_sha256', {}).get(str(midi))
                    if expected != digest(dest):
                        raise ValueError('Refusing to overwrite an unverified sample: ' + str(dest))
                else:
                    tasks.append((slug, midi, font, dest))
                reports[slug]['generated'].append(midi)
    print(json.dumps({s: {'range': [r['low'], r['high']], 'missing': r['generated'],
                          'soundfont': r['soundfont']} for s, r in reports.items()}, indent=2), flush=True)
    if not args.apply:
        return

    def render(task):
        slug, midi, font, dest = task
        if dest.exists():
            raise ValueError('Refusing to overwrite a generated sample: ' + str(dest))
        temporary = dest.with_suffix('.pending.wav')
        env = dict(os.environ, GENERALUSER_SF2=str(font), DRY='1' if slug == 'voice' else '0')
        subprocess.run(['bash', str(ROOT / 'scripts/audio/render_note.sh'), str(temporary),
                        str(PROGRAMS[slug]), '100', '3.2', str(midi)],
                       env=env, check=True, capture_output=True)
        if temporary.stat().st_size < 1000:
            raise RuntimeError('Empty generated sample: ' + str(temporary))
        stats = subprocess.run(['sox', str(temporary), '-n', 'stat'],
                               check=True, capture_output=True, text=True)
        peak = next(float(line.split(':')[1]) for line in stats.stderr.splitlines()
                    if line.startswith('Maximum amplitude:'))
        if peak < 0.00001:
            # Some matching GM patches leave a hole between playable keys.
            # Derive that single pitch from the nearest existing note in the
            # same verified bank, without changing duration or other recordings.
            sources = reports[slug]['sources']
            source_midi = min(sources, key=lambda value: abs(value - midi))
            subprocess.run(['sox', str(public / sources[source_midi]), '-b', '24',
                            str(temporary), 'pitch', str((midi - source_midi) * 100),
                            'gain', '-n', '-8'], check=True, capture_output=True)
            reports[slug]['pitch_shifted_from'][str(midi)] = source_midi
        temporary.replace(dest)
        return f'{slug}: MIDI {midi}'

    with ThreadPoolExecutor(max_workers=4) as pool:
        for result in pool.map(render, tasks):
            print(result, flush=True)
    for path, expected in originals.items():
        if digest(Path(path)) != expected:
            raise RuntimeError('Original sample changed: ' + path)
    for slug, report in reports.items():
        folder = public / 'notes' / slug
        if not all((folder / f'midi-{midi}.wav').is_file()
                   for midi in range(report['low'], report['high'] + 1)):
            raise RuntimeError('Incomplete bank: ' + slug)
        report['sample_sha256'] = {str(midi): digest(folder / f'midi-{midi}.wav')
                                   for midi in range(report['low'], report['high'] + 1)}
        temporary = folder / 'gm-bank.pending.json'
        temporary.write_text(json.dumps(report, indent=2))
        temporary.replace(folder / 'gm-bank.json')
    print(f'Generated {len(tasks)} missing notes. Existing source samples unchanged.', flush=True)


if __name__ == '__main__':
    main()
