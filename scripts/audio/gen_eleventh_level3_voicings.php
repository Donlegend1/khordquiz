<?php

// Voicing generator for "11th Degree Chords · Level 3" — key of C only.
// Left hand is ALWAYS the root-position triad (root/3rd/5th), fixed, never
// inverted. Two patterns for the right hand, both skipping root position:
//   Pattern A: upper triad (7th/9th/11th), inversions 1 and 2 -> 2 variants.
//   Pattern B: upper 7th chord (5th/7th/9th/11th — doubles the 5th already
//     in the left hand), inversions 1, 2, and 3 -> 3 variants.
// 5 variants per quality total. Confirmed by ear against the user's C
// major9(#11) example (see conversation).
//
// Usage: gen_eleventh_level3_voicings.php <base MIDI root>
// Prints one line per chord: <slug>\t<comma-separated MIDI notes>

const QUALITIES = [
    'maj9s11' => [0, 4, 7, 11, 14, 18],
    'minor11' => [0, 3, 7, 10, 14, 17],
    'dim7911' => [0, 3, 6, 9, 14, 17],
    'dom9s11' => [0, 4, 7, 10, 14, 18],
    'minor11b5' => [0, 3, 6, 10, 14, 17],
    'dom7b9s11' => [0, 4, 7, 10, 13, 18],
];

// N-note rotation: position k takes the bottom k notes of root position and
// moves each up an octave.
function rotation(array $notes, int $inv): array
{
    $n = count($notes);
    $rotated = array_merge(array_slice($notes, $inv), array_slice($notes, 0, $inv));
    for ($i = 0; $i < $inv; $i++) {
        $rotated[$n - $inv + $i] += 12;
    }

    return $rotated;
}

$base = (int) $argv[1];

foreach (QUALITIES as $qslug => $intervals) {
    [$i0, $i1, $i2, $i3, $i4, $i5] = $intervals;
    $left = [$base + $i0, $base + $i1, $base + $i2];
    $upperTriad = [$base + $i3, $base + $i4, $base + $i5];
    $upperSeventh = [$base + $i2, $base + $i3, $base + $i4, $base + $i5];

    foreach ([1, 2] as $inv) {
        $voicing = array_merge($left, rotation($upperTriad, $inv));
        sort($voicing);
        echo "{$qslug}-pAv{$inv}\t".implode(',', $voicing).PHP_EOL;
    }

    foreach ([1, 2, 3] as $inv) {
        $voicing = array_merge($left, rotation($upperSeventh, $inv));
        sort($voicing);
        echo "{$qslug}-pBv{$inv}\t".implode(',', $voicing).PHP_EOL;
    }
}
