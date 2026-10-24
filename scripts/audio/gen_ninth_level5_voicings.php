<?php

// Voicing generator for "9th Degree Chords · Level 5" — "two-hands, no
// root" voicing. Left hand plays a lower chord built from the 9th chord's
// own tones (root/3rd/5th for a triad, or root/3rd/5th/7th for a 7th
// chord), NEVER in root position. Right hand always plays the upper triad
// (5th/7th/9th), with its inversion = (left hand's inversion + 1) mod 3.
// Right hand is always placed ABOVE the left hand — if its natural
// (unshifted) register would overlap or sit below the left hand's top
// note, it's shifted up an octave until clear. Confirmed by ear against 5
// user-given examples for C major9 (see conversation).
//
// Pattern 1 (left = triad): left inversions 1,2 -> 2 variants.
// Pattern 2 (left = 7th chord): left inversions 1,2,3 -> 3 variants.
// 5 variants total per quality, key of C only.
//
// Usage: gen_ninth_level5_voicings.php <base MIDI root>
// Prints one line per chord: <slug>\t<comma-separated MIDI notes>

const QUALITIES = [
    'major9' => [0, 4, 7, 11, 14],
    'minor9' => [0, 3, 7, 10, 14],
    'dim7add9' => [0, 3, 6, 9, 14],
    'dominant9' => [0, 4, 7, 10, 14],
    'minor9b5' => [0, 3, 6, 10, 14],
    'dom7b9' => [0, 4, 7, 10, 13],
];

// N-note rotation (standard inversion technique): position k takes the
// bottom k notes of root position and moves each up an octave.
function rotation(array $notes, int $inv): array
{
    $n = count($notes);
    $rotated = array_merge(array_slice($notes, $inv), array_slice($notes, 0, $inv));
    for ($i = 0; $i < $inv; $i++) {
        $rotated[$n - $inv + $i] += 12;
    }

    return $rotated;
}

function shiftAboveLeft(array $right, int $leftMax): array
{
    while (min($right) <= $leftMax) {
        $right = array_map(fn ($n) => $n + 12, $right);
    }

    return $right;
}

$base = (int) $argv[1];

foreach (QUALITIES as $qslug => $intervals) {
    [$i0, $i1, $i2, $i3, $i4] = $intervals;
    $triadLeft = [$base + $i0, $base + $i1, $base + $i2];
    $seventhLeft = [$base + $i0, $base + $i1, $base + $i2, $base + $i3];
    $triadRight = [$base + $i2, $base + $i3, $base + $i4];

    // Pattern 1: triad left, inversions 1 and 2.
    foreach ([1, 2] as $leftInv) {
        $left = rotation($triadLeft, $leftInv);
        $rightInv = ($leftInv + 1) % 3;
        $right = shiftAboveLeft(rotation($triadRight, $rightInv), max($left));
        $voicing = array_merge($left, $right);
        sort($voicing);
        echo "{$qslug}-p1v{$leftInv}\t".implode(',', $voicing).PHP_EOL;
    }

    // Pattern 2: 7th-chord left, inversions 1, 2, and 3.
    foreach ([1, 2, 3] as $leftInv) {
        $left = rotation($seventhLeft, $leftInv);
        $rightInv = ($leftInv + 1) % 3;
        $right = shiftAboveLeft(rotation($triadRight, $rightInv), max($left));
        $voicing = array_merge($left, $right);
        sort($voicing);
        echo "{$qslug}-p2v{$leftInv}\t".implode(',', $voicing).PHP_EOL;
    }
}
