<?php

// Voicing generator for "11th Degree Chords · Level 1" — key of C only,
// root position, 6-note chords (root, 3rd, 5th, 7th, 9th, 11th). Each
// quality = its matching 9th Degree Chords quality + an 11th (perfect or
// sharp), same relationship 9th Degree Chords has to 7th Degree Chords.
// Usage: gen_eleventh_level1_voicings.php <base MIDI root>
// Prints one line per chord: <slug>\t<comma-separated MIDI notes>

const QUALITIES = [
    'maj9s11' => [0, 4, 7, 11, 14, 18],   // Major 9(#11)
    'minor11' => [0, 3, 7, 10, 14, 17],   // Minor 11th
    'dim7911' => [0, 3, 6, 9, 14, 17],    // Diminished 7(9,11)
    'dom9s11' => [0, 4, 7, 10, 14, 18],   // Dominant 9(#11)
    'minor11b5' => [0, 3, 6, 10, 14, 17], // Minor 11(b5)
    'dom7b9s11' => [0, 4, 7, 10, 13, 18], // Dominant 7(b9,#11)
];

$base = (int) $argv[1];

foreach (QUALITIES as $slug => $intervals) {
    $notes = array_map(fn ($i) => $base + $i, $intervals);
    sort($notes);
    echo "{$slug}\t".implode(',', $notes).PHP_EOL;
}
