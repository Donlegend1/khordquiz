<?php

// Voicing generator for "11th Degree Chords · Level 6" — same "avoid the
// root" voicing as Level 5, but across ALL 12 keys instead of just C.
// Usage: gen_eleventh_level6_voicings.php <base MIDI root for C>
// Prints one line per chord: <slug>\t<comma-separated MIDI notes>

const QUALITIES = [
    'maj9s11' => [0, 4, 7, 11, 14, 18],
    'minor11' => [0, 3, 7, 10, 14, 17],
    'dim7911' => [0, 3, 6, 9, 14, 17],
    'dom9s11' => [0, 4, 7, 10, 14, 18],
    'minor11b5' => [0, 3, 6, 10, 14, 17],
    'dom7b9s11' => [0, 4, 7, 10, 13, 18],
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

$cBase = (int) $argv[1];

foreach (KEYS as $kslug => $koff) {
    $base = $cBase + $koff;
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
                echo "{$kslug}-{$qslug}-{$comboSlug}{$inv}\t".implode(',', $voicing).PHP_EOL;
            }
        }
    }
}
