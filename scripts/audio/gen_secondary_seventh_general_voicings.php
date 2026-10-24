<?php

// Voicing generator for "Secondary 7th Chords · Level 9" (General) — all 11
// seventh-chord qualities (5 from 7th Degree Chords + 6 from Secondary 7th
// Chords), all 12 keys, all 4 rotated positions. Unlike Levels 6/8 in both
// source categories, NO qualities are excluded here — Diminished 7th and
// Dominant 7(b5) (both symmetric chords with ambiguous inversions) are
// included per explicit instruction, since General is a mixed-review quiz.
//
// Usage: gen_secondary_seventh_general_voicings.php <base MIDI root>
// Prints one line per chord: <slug>\t<comma-separated MIDI notes>

const QUALITIES = [
    // 7th Degree Chords' 5
    'diminished7' => [0, 3, 6, 9],
    'dominant7' => [0, 4, 7, 10],
    'm7b5' => [0, 3, 6, 10],
    'major7' => [0, 4, 7, 11],
    'minor7' => [0, 3, 7, 10],
    // Secondary 7th Chords' 6
    'aug7' => [0, 4, 8, 10],
    'minmaj7' => [0, 3, 7, 11],
    'dimmaj7' => [0, 3, 6, 11],
    'dom7b5' => [0, 4, 6, 10],
    'maj7s5' => [0, 4, 8, 11],
    'maj7b5' => [0, 4, 6, 11],
];

const KEYS = [
    'c' => 0, 'db' => 1, 'd' => 2, 'eb' => 3, 'e' => 4, 'f' => 5,
    'gb' => 6, 'g' => 7, 'ab' => 8, 'a' => 9, 'bb' => 10, 'b' => 11,
];

function rotations(array $intervals, int $root): array
{
    $notes = array_map(fn ($i) => $root + $i, $intervals);
    sort($notes);
    [$n0, $n1, $n2, $n3] = $notes;

    return [
        'root' => [$n0, $n1, $n2, $n3],
        '1st' => [$n1, $n2, $n3, $n0 + 12],
        '2nd' => [$n2, $n3, $n0 + 12, $n1 + 12],
        '3rd' => [$n3, $n0 + 12, $n1 + 12, $n2 + 12],
    ];
}

$base = (int) $argv[1];

foreach (KEYS as $kslug => $koff) {
    $root = $base + $koff;
    foreach (QUALITIES as $qslug => $intervals) {
        foreach (rotations($intervals, $root) as $pslug => $voicing) {
            echo "{$kslug}-{$qslug}-{$pslug}\t".implode(',', $voicing).PHP_EOL;
        }
    }
}
