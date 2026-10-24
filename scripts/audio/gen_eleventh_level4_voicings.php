<?php

// Voicing generator for "11th Degree Chords · Level 4" — same shape as
// Level 3 (fixed root-position triad left, inverted upper-triad/7th
// right), but across ALL 12 keys instead of just C.
// Usage: gen_eleventh_level4_voicings.php <base MIDI root for C>
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
        $left = [$base + $i0, $base + $i1, $base + $i2];
        $upperTriad = [$base + $i3, $base + $i4, $base + $i5];
        $upperSeventh = [$base + $i2, $base + $i3, $base + $i4, $base + $i5];

        foreach ([1, 2] as $inv) {
            $voicing = array_merge($left, rotation($upperTriad, $inv));
            sort($voicing);
            echo "{$kslug}-{$qslug}-pAv{$inv}\t".implode(',', $voicing).PHP_EOL;
        }

        foreach ([1, 2, 3] as $inv) {
            $voicing = array_merge($left, rotation($upperSeventh, $inv));
            sort($voicing);
            echo "{$kslug}-{$qslug}-pBv{$inv}\t".implode(',', $voicing).PHP_EOL;
        }
    }
}
