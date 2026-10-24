<?php

// Voicing generator for "11th Degree Chords · Level 5" — "avoid the root"
// voicing, key of C only. Left hand = either the root-position triad
// (root/3rd/5th) or the root-position 7th chord (root/3rd/5th/7th). Right
// hand = either the upper triad (7th/9th/11th) or the upper 7th chord
// (5th/7th/9th/11th, doubling the 5th from the left). 4 combinations
// (triad/triad, triad/7th, 7th/triad, 7th/7th), each with 2 audio variants:
// both hands moved to their 1st inversion together, then both to their 2nd
// inversion together (same rotation() technique used throughout, same
// inversion NUMBER applied independently to each hand — a triad's "2nd
// inversion" and a 7th chord's "2nd inversion" are structurally different
// sizes but share the inversion index). 8 variants per quality total,
// never root position on either hand. Confirmed by ear against the user's
// C major9(#11) example (see conversation).
//
// Usage: gen_eleventh_level5_voicings.php <base MIDI root>
// Prints one line per chord: <slug>\t<comma-separated MIDI notes>

const QUALITIES = [
    'maj9s11' => [0, 4, 7, 11, 14, 18],
    'minor11' => [0, 3, 7, 10, 14, 17],
    'dim7911' => [0, 3, 6, 9, 14, 17],
    'dom9s11' => [0, 4, 7, 10, 14, 18],
    'minor11b5' => [0, 3, 6, 10, 14, 17],
    'dom7b9s11' => [0, 4, 7, 10, 13, 18],
];

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

    $leftTriad = [$base + $i0, $base + $i1, $base + $i2];
    $leftSeventh = [$base + $i0, $base + $i1, $base + $i2, $base + $i3];
    $rightTriad = [$base + $i3, $base + $i4, $base + $i5];
    $rightSeventh = [$base + $i2, $base + $i3, $base + $i4, $base + $i5];

    $combos = [
        'tt' => [$leftTriad, $rightTriad],
        'ts' => [$leftTriad, $rightSeventh],
        'st' => [$leftSeventh, $rightTriad],
        'ss' => [$leftSeventh, $rightSeventh],
    ];

    foreach ($combos as $comboSlug => [$left, $right]) {
        foreach ([1, 2] as $inv) {
            $voicing = array_merge(rotation($left, $inv), rotation($right, $inv));
            sort($voicing);
            echo "{$qslug}-{$comboSlug}{$inv}\t".implode(',', $voicing).PHP_EOL;
        }
    }
}
