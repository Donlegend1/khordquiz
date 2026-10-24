<?php

// Computes the 192 drop-2 voicings for Add 9 · Level 8 (12 keys x 4
// qualities x 4 rotated positions) by rotating each key/quality's root
// voicing (same technique as Level 4) then applying chordalDrop2() to the
// already-rotated 4-note voicing — same relationship Level 8 has to Level 4
// that Level 7 has to Level 3. Prints one line per chord:
// <keyslug>-<qualityslug>-<posslug>\t<comma-separated MIDI notes>

require __DIR__.'/chordal_melodies_pool.php';

const QUALITIES = [
    'major' => [0, 2, 4, 7], 'minor' => [0, 2, 3, 7],
    'diminished' => [0, 2, 3, 6], 'augmented' => [0, 2, 4, 8],
];

const KEYS = [
    'c' => 0, 'db' => 1, 'd' => 2, 'eb' => 3, 'e' => 4, 'f' => 5,
    'gb' => 6, 'g' => 7, 'ab' => 8, 'a' => 9, 'bb' => 10, 'b' => 11,
];

const BASE = 60; // C4

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

foreach (KEYS as $keySlug => $keyOffset) {
    $root = BASE + $keyOffset;
    foreach (QUALITIES as $qualitySlug => $intervals) {
        $rots = rotations($intervals, $root);
        foreach ($rots as $posSlug => $voicing) {
            $drop = chordalDrop2($voicing);
            echo "{$keySlug}-{$qualitySlug}-{$posSlug}\t".implode(',', $drop).PHP_EOL;
        }
    }
}
