#!/usr/bin/env python3
"""Prints one line per Bass Guitar file to render for Basic Triads Levels 1-8:
<output_path>\t<note1,note2,note3>

Voicing logic mirrors each level's build_basic_triads_levelN.php exactly
(same quality intervals, same standard-inversion formula, same chordalDrop2),
using Piano's register (build_basic_triads_level5.php's own comment confirms
"Every other instrument shares Piano's register" and that Bass Guitar was
the one deliberately shifted down an octave from it) — so Bass Guitar should
render the SAME absolute notes as Piano, just with program 33 instead of 0.
"""

QUALITY_INTERVALS = {
    'major': [0, 4, 7], 'minor': [0, 3, 7], 'diminished': [0, 3, 6],
    'augmented': [0, 4, 8], 'sus4': [0, 5, 7], 'sus2': [0, 2, 7],
}

KEYS_12 = ['c', 'db', 'd', 'eb', 'e', 'f', 'gb', 'g', 'ab', 'a', 'bb', 'b']


def root_position(root, intervals):
    return sorted(root + i for i in intervals)


def inversions(root, intervals):
    n0, n1, n2 = root_position(root, intervals)
    return {
        'root': [n0, n1, n2],
        '1st': [n1, n2, n0 + 12],
        '2nd': [n2, n0 + 12, n1 + 12],
    }


def drop2(voicing):
    n = len(voicing)
    dropped = voicing[n - 2] - 12
    rest = [v for i, v in enumerate(voicing) if i != n - 2]
    rest.append(dropped)
    return sorted(rest)


lines = []

# Level 1: C only, root position, 6 qualities, base 48.
BASE_L1 = 48
L1_SLUGS = ['major', 'minor', 'diminished', 'augmented', 'sus4', 'sus2']
for slug in L1_SLUGS:
    notes = root_position(BASE_L1, QUALITY_INTERVALS[slug])
    lines.append((f"basic-triads/bass-guitar/{slug}.wav", notes))

# Level 2: 12 keys, root position, 6 qualities, base 48.
for ki, keyslug in enumerate(KEYS_12):
    root = BASE_L1 + ki
    for slug in L1_SLUGS:
        notes = root_position(root, QUALITY_INTERVALS[slug])
        lines.append((f"basic-triads/level2/bass-guitar/{keyslug}-{slug}.wav", notes))

# Level 3: C only, drop-2, 6 qualities, base 60.
BASE_DROP2 = 60
for slug in L1_SLUGS:
    notes = drop2(root_position(BASE_DROP2, QUALITY_INTERVALS[slug]))
    lines.append((f"basic-triads/level3/bass-guitar/{slug}.wav", notes))

# Level 4: 12 keys, drop-2, 6 qualities, base 60.
for ki, keyslug in enumerate(KEYS_12):
    root = BASE_DROP2 + ki
    for slug in L1_SLUGS:
        notes = drop2(root_position(root, QUALITY_INTERVALS[slug]))
        lines.append((f"basic-triads/level4/bass-guitar/{keyslug}-{slug}.wav", notes))

# Level 5: C only, all inversions, 5 qualities (Suspended = sus2 only), base 48.
L5_QUALITIES = {
    'major': [0, 4, 7], 'minor': [0, 3, 7], 'diminished': [0, 3, 6],
    'augmented': [0, 4, 8], 'suspended': [0, 2, 7],
}
for qslug, intervals in L5_QUALITIES.items():
    invs = inversions(BASE_L1, intervals)
    for invslug, notes in invs.items():
        lines.append((f"basic-triads/level5/bass-guitar/{qslug}-{invslug}.wav", notes))

# Level 6: 12 keys, all inversions, 3 qualities (Major/Minor/Diminished), base 48.
L6_QUALITIES = {'major': [0, 4, 7], 'minor': [0, 3, 7], 'diminished': [0, 3, 6]}
for ki, keyslug in enumerate(KEYS_12):
    root = BASE_L1 + ki
    for qslug, intervals in L6_QUALITIES.items():
        invs = inversions(root, intervals)
        for invslug, notes in invs.items():
            lines.append((f"basic-triads/level6/bass-guitar/{keyslug}-{qslug}-{invslug}.wav", notes))

# Level 7: C only, drop-2, all inversions, 5 qualities, base 60.
for qslug, intervals in L5_QUALITIES.items():
    invs = inversions(BASE_DROP2, intervals)
    for invslug, notes in invs.items():
        lines.append((f"basic-triads/level7/bass-guitar/{qslug}-{invslug}.wav", drop2(notes)))

# Level 8: 12 keys, drop-2, all inversions, 3 qualities, base 60.
for ki, keyslug in enumerate(KEYS_12):
    root = BASE_DROP2 + ki
    for qslug, intervals in L6_QUALITIES.items():
        invs = inversions(root, intervals)
        for invslug, notes in invs.items():
            lines.append((f"basic-triads/level8/bass-guitar/{keyslug}-{qslug}-{invslug}.wav", drop2(notes)))

for path, notes in lines:
    print(f"{path}\t{','.join(str(n) for n in notes)}")
