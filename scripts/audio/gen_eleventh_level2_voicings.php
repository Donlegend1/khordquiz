<?php

// Voicing generator for "11th Degree Chords · Level 2" — same 6 qualities
// as Level 1, root position, but across ALL 12 keys.
// Usage: gen_eleventh_level2_voicings.php <base MIDI root for C>
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

$cBase = (int) $argv[1];

foreach (KEYS as $kslug => $koff) {
    $base = $cBase + $koff;
    foreach (QUALITIES as $qslug => $intervals) {
        $notes = array_map(fn ($i) => $base + $i, $intervals);
        sort($notes);
        echo "{$kslug}-{$qslug}\t".implode(',', $notes).PHP_EOL;
    }
}
