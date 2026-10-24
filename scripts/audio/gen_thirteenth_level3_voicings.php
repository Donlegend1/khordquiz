<?php

// Voicing generator for "13th Degree Chords · Level 3" — key of C only.
// Left hand fixed at root-position triad (root/3rd/5th). Right hand = the
// upper 4-note structure (7th/9th/11th/13th), inverted through 1st and
// 2nd inversion only. 2 variants per quality. Confirmed by ear against the
// user's C major13(#11) example (see conversation).
//
// Usage: gen_thirteenth_level3_voicings.php <base MIDI root>
// Prints one line per chord: <slug>\t<comma-separated MIDI notes>

const QUALITIES = [
    'maj13s11' => [0, 4, 7, 11, 14, 18, 21],
    'minor13' => [0, 3, 7, 10, 14, 17, 21],
    'dom13s11' => [0, 4, 7, 10, 14, 18, 21],
    'dom13b9s11' => [0, 4, 7, 10, 13, 18, 21],
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
    [$i0, $i1, $i2, $i3, $i4, $i5, $i6] = $intervals;
    $left = [$base + $i0, $base + $i1, $base + $i2];
    $right = [$base + $i3, $base + $i4, $base + $i5, $base + $i6];

    foreach ([1, 2] as $inv) {
        $voicing = array_merge($left, rotation($right, $inv));
        sort($voicing);
        echo "{$qslug}-v{$inv}\t".implode(',', $voicing).PHP_EOL;
    }
}
