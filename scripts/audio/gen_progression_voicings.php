<?php

// Shared voicing + answer generator for the 5 "Chord Progressions" quizzes
// that were stuck on a single row padded to 25 (Diatonic Progression,
// Progression Build #1/#2, Progression recognition, 2-5-1 chord degree —
// Chordal Progressions already has real varied content and isn't touched).
// Ports app/practice.tsx's exact per-questionIndex degree/quality/extensions
// formula (correctChords in practice.tsx) so each of the 25 rows is
// genuinely distinct, then builds real MIDI voicings for a chord-by-chord
// progression in the key of C.
//
// Usage: gen_progression_voicings.php <preset-key> <base MIDI root for C>
// Prints one line per question (25 total): <slug>\t<answer>\t<chord1 notes>;<chord2 notes>;...
// Each "<chordN notes>" is comma-separated MIDI numbers.

const SCALE_DEGREE_OFFSET = [1 => 0, 2 => 2, 3 => 4, 4 => 5, 5 => 7, 6 => 9, 7 => 11];

const QUALITY_INTERVALS = [
    'Major' => [0, 4, 7], 'Minor' => [0, 3, 7], 'Diminished' => [0, 3, 6], 'Augmented' => [0, 4, 8],
    'Sus 2' => [0, 2, 7], 'Sus 4' => [0, 5, 7],
    'Major 7th' => [0, 4, 7, 11], 'Minor 7th' => [0, 3, 7, 10], 'Dominant 7th' => [0, 4, 7, 10],
    'Minor 7(♭5)' => [0, 3, 6, 10], 'Minor 6' => [0, 3, 7, 9], 'Major 6' => [0, 4, 7, 9],
];

// DEGREE_EXTENSIONS pool (2-5-1 chord degree only) — ♭5/Sus2/Sus4 override
// the 3rd/5th, the rest add an upper extension on top of the base quality.
const EXTENSION_INTERVAL = ['♭9' => 13, '9' => 14, '♯9' => 15, '11' => 17, '♯11' => 18, '♭13' => 20, '13' => 21];

const PRESETS = [
    'diatonic-progression' => [
        'title' => 'Diatonic Progression', 'folder' => 'diatonic-progression', 'length' => 4,
        'degrees' => ['1', '2', '3', '4', '5', '6', '7'], 'qualities' => [],
        'fixedQualities' => [1 => 'Major', 2 => 'Minor', 3 => 'Minor', 4 => 'Major', 5 => 'Major', 6 => 'Minor', 7 => 'Diminished'],
        'fixedDegrees' => null, 'extensions' => null,
    ],
    'progression-build-1' => [
        'title' => 'Progression Build #1', 'folder' => 'progression-build-1', 'length' => 4,
        'degrees' => ['1', '2', '3', '4', '5', '6', '7'], 'qualities' => ['Major', 'Minor', 'Diminished', 'Augmented', 'Sus 2', 'Sus 4'],
        'fixedQualities' => null, 'fixedDegrees' => null, 'extensions' => null,
    ],
    'progression-build-2' => [
        'title' => 'Progression Build #2', 'folder' => 'progression-build-2', 'length' => 5,
        'degrees' => ['1', '2', '3', '4', '5', '6', '7'], 'qualities' => ['Major 7th', 'Minor 7th', 'Dominant 7th', 'Minor 7(♭5)'],
        'fixedQualities' => null, 'fixedDegrees' => null, 'extensions' => null,
    ],
    'progression-recognition' => [
        'title' => 'Progression recognition', 'folder' => 'progression-recognition', 'length' => 3,
        'degrees' => [], 'qualities' => ['Major 7th', 'Minor 7th', 'Minor 7(♭5)', 'Dominant 7th', 'Minor 6', 'Major 6'],
        'fixedQualities' => null, 'fixedDegrees' => ['2', '5', '1'], 'extensions' => null,
    ],
    '2-5-1-chord-degree' => [
        'title' => '2-5-1 chord degree', 'folder' => '2-5-1-chord-degree', 'length' => 3,
        'degrees' => [], 'qualities' => ['Major 7th', 'Minor 7th', 'Minor 7(♭5)', 'Dominant 7th', 'Minor 6', 'Major 6'],
        'fixedQualities' => null, 'fixedDegrees' => ['2', '5', '1'],
        'extensions' => ['♭9', '9', '♯9', '11', '♯11', '♭13', '13', 'Sus 4', 'Sus 2', '♭5'],
    ],
];

function chordVoicing(string $degree, string $quality, array $extensions, int $base): array
{
    $root = $base + SCALE_DEGREE_OFFSET[(int) $degree];
    $intervals = QUALITY_INTERVALS[$quality];

    foreach ($extensions as $extension) {
        if ($extension === 'Sus 2') {
            $intervals[1] = 2;
        } elseif ($extension === 'Sus 4') {
            $intervals[1] = 5;
        } elseif ($extension === '♭5') {
            $intervals[2] = 6;
        } elseif (isset(EXTENSION_INTERVAL[$extension])) {
            $intervals[] = EXTENSION_INTERVAL[$extension];
        }
    }

    $intervals = array_values(array_unique($intervals));
    sort($intervals);

    return array_map(fn ($i) => $root + $i, $intervals);
}

function chordText(string $degree, string $quality, array $extensions): string
{
    return "{$degree} {$quality}".(count($extensions) ? '('.implode(', ', $extensions).')' : '');
}

$presetKey = $argv[1];
$base = (int) $argv[2];
$preset = PRESETS[$presetKey];
$length = $preset['length'];

for ($questionIndex = 0; $questionIndex < 25; $questionIndex++) {
    $chords = [];
    for ($index = 0; $index < $length; $index++) {
        if ($preset['fixedDegrees']) {
            $degree = $preset['fixedDegrees'][$index];
        } else {
            $degree = $index === 0
                ? $preset['degrees'][0]
                : $preset['degrees'][($questionIndex * 3 + $index * 2) % count($preset['degrees'])];
        }

        $quality = $preset['fixedQualities']
            ? $preset['fixedQualities'][(int) $degree]
            : $preset['qualities'][($questionIndex + $index * (1 + ($questionIndex % 2))) % count($preset['qualities'])];

        $extensions = [];
        if ($preset['extensions']) {
            $extensionCount = ($questionIndex + $index) % 3;
            $pool = $preset['extensions'];
            $seen = [];
            for ($offset = 0; $offset < $extensionCount; $offset++) {
                $extension = $pool[($questionIndex * 2 + $index * 3 + $offset * 4) % count($pool)];
                if (! in_array($extension, $seen, true)) {
                    $seen[] = $extension;
                }
            }
            $extensions = $seen;
        }

        $chords[] = ['degree' => $degree, 'quality' => $quality, 'extensions' => $extensions];
    }

    $answer = implode(' - ', array_map(fn ($c) => chordText($c['degree'], $c['quality'], $c['extensions']), $chords));
    $noteGroups = array_map(fn ($c) => implode(',', chordVoicing($c['degree'], $c['quality'], $c['extensions'], $base)), $chords);
    $slug = $preset['folder'].'-q'.$questionIndex;

    echo "{$slug}\t{$answer}\t".implode(';', $noteGroups).PHP_EOL;
}
