<?php

// Voicing generator for "9th Degree Chords · Level 6" — same "two-hands,
// no-root" voicing as Level 5 (see gen_ninth_level5_voicings.php for the
// full rule), but across ALL 12 keys instead of just C.
//
// Usage: gen_ninth_level6_voicings.php <base MIDI root for C>
// Prints one line per chord: <slug>\t<comma-separated MIDI notes>

const QUALITIES = [
    'major9' => [0, 4, 7, 11, 14],
    'minor9' => [0, 3, 7, 10, 14],
    'dim7add9' => [0, 3, 6, 9, 14],
    'dominant9' => [0, 4, 7, 10, 14],
    'minor9b5' => [0, 3, 6, 10, 14],
    'dom7b9' => [0, 4, 7, 10, 13],
];

const KEYS = [
    'c' => 0, 'db' => 1, 'd' => 2, 'eb' => 3, 'e' => 4, 'f' => 5,
    'gb' => 6, 'g' => 7, 'ab' => 8, 'a' => 9, 'bb' => 10, 'b' => 11,
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

function shiftAboveLeft(array $right, int $leftMax): array
{
    while (min($right) <= $leftMax) {
        $right = array_map(fn ($n) => $n + 12, $right);
    }

    return $right;
}

$cBase = (int) $argv[1];

foreach (KEYS as $kslug => $koff) {
    $base = $cBase + $koff;
    foreach (QUALITIES as $qslug => $intervals) {
        [$i0, $i1, $i2, $i3, $i4] = $intervals;
        $triadLeft = [$base + $i0, $base + $i1, $base + $i2];
        $seventhLeft = [$base + $i0, $base + $i1, $base + $i2, $base + $i3];
        $triadRight = [$base + $i2, $base + $i3, $base + $i4];

        foreach ([1, 2] as $leftInv) {
            $left = rotation($triadLeft, $leftInv);
            $rightInv = ($leftInv + 1) % 3;
            $right = shiftAboveLeft(rotation($triadRight, $rightInv), max($left));
            $voicing = array_merge($left, $right);
            sort($voicing);
            echo "{$kslug}-{$qslug}-p1v{$leftInv}\t".implode(',', $voicing).PHP_EOL;
        }

        foreach ([1, 2, 3] as $leftInv) {
            $left = rotation($seventhLeft, $leftInv);
            $rightInv = ($leftInv + 1) % 3;
            $right = shiftAboveLeft(rotation($triadRight, $rightInv), max($left));
            $voicing = array_merge($left, $right);
            sort($voicing);
            echo "{$kslug}-{$qslug}-p2v{$leftInv}\t".implode(',', $voicing).PHP_EOL;
        }
    }
}
