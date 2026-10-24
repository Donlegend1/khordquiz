<?php

// Voicing generator for "13th Degree Chords · Level 1" — key of C only,
// root position, 4 qualities (7-note chords: root,3rd,5th,7th,9th,11th,13th).
// Intervals derived directly from the user's spelled-out notes:
//   Major 13(#11):        C E G B D F# A
//   Minor 13th:            C Eb G Bb D F A
//   Dominant 13(#11):      C E G Bb D F# A
//   Dominant 13(b9,#11):   C E G Bb Db F# A
//
// Usage: gen_thirteenth_level1_voicings.php <base MIDI root>
// Prints one line per chord: <slug>\t<comma-separated MIDI notes>

const QUALITIES = [
    'maj13s11' => [0, 4, 7, 11, 14, 18, 21],
    'minor13' => [0, 3, 7, 10, 14, 17, 21],
    'dom13s11' => [0, 4, 7, 10, 14, 18, 21],
    'dom13b9s11' => [0, 4, 7, 10, 13, 18, 21],
];

$base = (int) $argv[1];

foreach (QUALITIES as $slug => $intervals) {
    $notes = array_map(fn ($i) => $base + $i, $intervals);
    sort($notes);
    echo "{$slug}\t".implode(',', $notes).PHP_EOL;
}
