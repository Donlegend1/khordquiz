<?php

// Voicing generator for "9th Degree Chords" Levels 1-2 (root position only,
// 5-note chords: root, 3rd, 5th, 7th, 9th). Level 3/4 (drop-2) to follow.
// Usage: gen_ninth_voicings.php <level 1-2> <base MIDI root>
// Prints one line per chord: <slug>\t<comma-separated MIDI notes>

const QUALITIES = [
    'major9' => [0, 4, 7, 11, 14],      // Major 9th
    'minor9' => [0, 3, 7, 10, 14],      // Minor 9th
    'dim7add9' => [0, 3, 6, 9, 14],     // Diminished 7(9)
    'dominant9' => [0, 4, 7, 10, 14],   // Dominant 9th
    'minor9b5' => [0, 3, 6, 10, 14],    // Minor 9(b5)
    'dom7b9' => [0, 4, 7, 10, 13],      // Dominant 7(b9)
];

const KEYS = [
    'c' => 0, 'db' => 1, 'd' => 2, 'eb' => 3, 'e' => 4, 'f' => 5,
    'gb' => 6, 'g' => 7, 'ab' => 8, 'a' => 9, 'bb' => 10, 'b' => 11,
];

$level = (int) $argv[1];
$base = (int) $argv[2];

$lines = [];

switch ($level) {
    case 1:
        foreach (QUALITIES as $qslug => $intervals) {
            $notes = array_map(fn ($i) => $base + $i, $intervals);
            sort($notes);
            $lines["{$qslug}"] = $notes;
        }
        break;

    case 2:
        foreach (KEYS as $kslug => $koff) {
            $root = $base + $koff;
            foreach (QUALITIES as $qslug => $intervals) {
                $notes = array_map(fn ($i) => $root + $i, $intervals);
                sort($notes);
                $lines["{$kslug}-{$qslug}"] = $notes;
            }
        }
        break;

    default:
        fwrite(STDERR, "Unknown level {$level}\n");
        exit(1);
}

foreach ($lines as $slug => $notes) {
    echo "{$slug}\t".implode(',', $notes).PHP_EOL;
}
