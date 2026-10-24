<?php

// Voicing generator for "Chord Tone Recognition #1" — the 5 7th Degree
// Chord qualities + the 6 Secondary 7th Chord qualities, root position
// only, all 12 keys, each of the 4 chord tones (1/3/5/7) highlighted in
// turn. Usage: gen_chord_tone_recognition1_voicings.php <base MIDI root for C>
// Prints one line per combo: <slug>\t<tone label>\t<tone MIDI note>\t<comma-separated chord MIDI notes>

const QUALITIES = [
    // From 7th Degree Chords.
    'diminished7' => ['Diminished 7th', [0, 3, 6, 9]],
    'dominant7' => ['Dominant 7th', [0, 4, 7, 10]],
    'm7b5' => ['Minor 7(♭5)', [0, 3, 6, 10]],
    'major7' => ['Major 7th', [0, 4, 7, 11]],
    'minor7' => ['Minor 7th', [0, 3, 7, 10]],
    // From Secondary 7th Chords.
    'aug7' => ['Augmented 7th', [0, 4, 8, 10]],
    'minmaj7' => ['Minor-Major 7th', [0, 3, 7, 11]],
    'dimmaj7' => ['Diminished-Major 7th', [0, 3, 6, 11]],
    'dom7b5' => ['Dominant 7(♭5)', [0, 4, 6, 10]],
    'maj7s5' => ['Major 7(♯5)', [0, 4, 8, 11]],
    'maj7b5' => ['Major 7(♭5)', [0, 4, 6, 11]],
];

const DEGREES = ['1', '3', '5', '7'];

const KEYS = [
    'c' => 0, 'db' => 1, 'd' => 2, 'eb' => 3, 'e' => 4, 'f' => 5,
    'gb' => 6, 'g' => 7, 'ab' => 8, 'a' => 9, 'bb' => 10, 'b' => 11,
];

$cBase = (int) $argv[1];

foreach (KEYS as $kslug => $koff) {
    $base = $cBase + $koff;
    foreach (QUALITIES as $qslug => [$qlabel, $intervals]) {
        $chordNotes = array_map(fn ($i) => $base + $i, $intervals);
        foreach ($intervals as $idx => $interval) {
            $degree = DEGREES[$idx];
            $toneNote = $base + $interval;
            echo "{$kslug}-{$qslug}-{$degree}\t{$qlabel}\t{$toneNote}\t".implode(',', $chordNotes).PHP_EOL;
        }
    }
}
