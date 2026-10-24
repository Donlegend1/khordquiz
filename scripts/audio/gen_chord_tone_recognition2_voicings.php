<?php

// Voicing generator for "Chord Tone Recognition #2" — same 11 qualities as
// #1 (5 from 7th Degree Chords + 6 from Secondary 7th Chords), but using
// the 1st, 2nd, and 3rd inversions instead of root position — all 12 keys,
// each of the 4 chord tones (1/3/5/7) highlighted in turn, at the pitch it
// actually sits at within that inversion's voicing.
// Usage: gen_chord_tone_recognition2_voicings.php <base MIDI root for C>
// Prints one line per combo: <slug>\t<tone label>\t<tone MIDI note>\t<comma-separated chord MIDI notes>

const QUALITIES = [
    'diminished7' => ['Diminished 7th', [0, 3, 6, 9]],
    'dominant7' => ['Dominant 7th', [0, 4, 7, 10]],
    'm7b5' => ['Minor 7(♭5)', [0, 3, 6, 10]],
    'major7' => ['Major 7th', [0, 4, 7, 11]],
    'minor7' => ['Minor 7th', [0, 3, 7, 10]],
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
    foreach (QUALITIES as $qslug => [$qlabel, $intervals]) {
        $rootPosition = array_map(fn ($i) => $base + $i, $intervals);

        foreach ([1, 2, 3] as $inv) {
            $voicing = rotation($rootPosition, $inv);
            sort($voicing);

            foreach ($intervals as $idx => $interval) {
                $degree = DEGREES[$idx];
                // rotation() only octave-shifts the notes it wraps to the back
                // (original indices 0..inv-1) — so the pitch for original
                // scale-degree index $idx is just its root-position pitch,
                // +12 if it was one of the wrapped notes.
                $toneNote = $rootPosition[$idx] + ($idx < $inv ? 12 : 0);
                echo "{$kslug}-{$qslug}-inv{$inv}-{$degree}\t{$qlabel}\t{$toneNote}\t".implode(',', $voicing).PHP_EOL;
            }
        }
    }
}
