<?php

// Voicing generator for "Secondary 9th Chords · Level 4" — same shape as
// Level 3, but across ALL 12 keys instead of just C.
// Usage: gen_secondary_ninth_level4_voicings.php <base MIDI root for C>
// Prints one line per chord: <slug>\t<comma-separated MIDI notes>

const QUALITIES = [
    'maj9s5' => [0, 4, 8, 11, 14],
    'dimmaj9' => [0, 3, 6, 11, 14],
    'dom9b5' => [0, 4, 6, 10, 14],
    'minmaj9' => [0, 3, 7, 11, 14],
    'aug9' => [0, 4, 8, 10, 14],
    'maj9b5' => [0, 4, 6, 11, 14],
];

const KEYS = [
    'c' => 0, 'db' => 1, 'd' => 2, 'eb' => 3, 'e' => 4, 'f' => 5,
    'gb' => 6, 'g' => 7, 'ab' => 8, 'a' => 9, 'bb' => 10, 'b' => 11,
];

const POSITIONS = ['root', '1st', '2nd', '3rd'];

function rotations(array $upper): array
{
    [$n0, $n1, $n2, $n3] = $upper;

    return [
        'root' => [$n0, $n1, $n2, $n3],
        '1st' => [$n1, $n2, $n3, $n0 + 12],
        '2nd' => [$n2, $n3, $n0 + 12, $n1 + 12],
        '3rd' => [$n3, $n0 + 12, $n1 + 12, $n2 + 12],
    ];
}

function doubleAbove(int $note, int $above): int
{
    while ($note <= $above) {
        $note += 12;
    }

    return $note;
}

$cBase = (int) $argv[1];

foreach (KEYS as $kslug => $koff) {
    $base = $cBase + $koff;
    foreach (QUALITIES as $qslug => $intervals) {
        [$i0, $i1, $i2, $i3, $i4] = $intervals;
        $root = $base + $i0;
        $upper = [$base + $i1, $base + $i2, $base + $i3, $base + $i4];
        $chordFifth = $base + $i2;
        $chordSeventh = $base + $i3;
        $rots = rotations($upper);

        foreach (POSITIONS as $posSlug) {
            $voicing = array_merge([$root], $rots[$posSlug]);

            if ($posSlug !== 'root') {
                $voicing[] = doubleAbove($chordFifth, max($voicing));
            }
            if ($posSlug === '3rd') {
                $voicing[] = doubleAbove($chordSeventh, max($voicing));
            }

            sort($voicing);
            echo "{$kslug}-{$qslug}-{$posSlug}\t".implode(',', $voicing).PHP_EOL;
        }
    }
}
