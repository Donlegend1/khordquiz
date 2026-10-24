<?php

// Voicing generator for "Secondary 9th Chords" Levels 1-2 (root position
// only, 5-note chords: root, 3rd, 5th, 7th, 9th). Each quality = its
// matching Secondary 7th Chords quality + a major 9th (+14 semitones).
// Usage: gen_secondary_ninth_voicings.php <level 1-2> <base MIDI root>
// Prints one line per chord: <slug>\t<comma-separated MIDI notes>

const QUALITIES = [
    'maj9s5' => [0, 4, 8, 11, 14],     // Major 9(#5)
    'dimmaj9' => [0, 3, 6, 11, 14],    // Diminished-Major 9th
    'dom9b5' => [0, 4, 6, 10, 14],     // Dominant 9(b5)
    'minmaj9' => [0, 3, 7, 11, 14],    // Minor-Major 9th
    'aug9' => [0, 4, 8, 10, 14],       // Augmented 9th
    'maj9b5' => [0, 4, 6, 11, 14],     // Major 9(b5)
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
            $lines[$qslug] = $notes;
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
