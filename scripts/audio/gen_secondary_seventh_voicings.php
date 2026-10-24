<?php

// Generalized voicing generator for "Secondary 7th Chords" Levels 1-8,
// mirroring 7th Degree Chords' exact structure: Levels 1-4 root position
// only (flat quality answer), Levels 5-8 all 4 rotated positions (compound
// quality+inversion answer). Levels 3/4/7/8 are drop-2. Levels 6/8 (12-key,
// all inversions) exclude Dominant 7(b5) — it's a tritone-symmetric chord
// (two tritones stacked a major 3rd apart: [0,4,6,10] maps onto itself
// transposed a tritone), so its inversions are indistinguishable without a
// key reference, same reasoning as 7th Degree Chords excluding Diminished
// 7th from its own Levels 6/8.
//
// Usage: gen_secondary_seventh_voicings.php <level 1-8> <base MIDI root>
// Prints one line per chord: <slug>\t<comma-separated MIDI notes>

require __DIR__.'/chordal_melodies_pool.php';

const QUALITIES = [
    'aug7' => [0, 4, 8, 10],       // Augmented 7th (augmented triad + m7)
    'minmaj7' => [0, 3, 7, 11],    // Minor-Major 7th
    'dimmaj7' => [0, 3, 6, 11],    // Diminished-Major 7th
    'dom7b5' => [0, 4, 6, 10],     // Dominant 7(b5)
    'maj7s5' => [0, 4, 8, 11],     // Major 7(#5)
    'maj7b5' => [0, 4, 6, 11],     // Major 7(b5)
];

// Levels 6 & 8 only — Dominant 7(b5) excluded (tritone-symmetric, see above).
const QUALITIES_NO_SYMMETRIC = [
    'aug7' => [0, 4, 8, 10],
    'minmaj7' => [0, 3, 7, 11],
    'dimmaj7' => [0, 3, 6, 11],
    'maj7s5' => [0, 4, 8, 11],
    'maj7b5' => [0, 4, 6, 11],
];

const KEYS = [
    'c' => 0, 'db' => 1, 'd' => 2, 'eb' => 3, 'e' => 4, 'f' => 5,
    'gb' => 6, 'g' => 7, 'ab' => 8, 'a' => 9, 'bb' => 10, 'b' => 11,
];

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

    case 3:
        foreach (QUALITIES as $qslug => $intervals) {
            $notes = array_map(fn ($i) => $base + $i, $intervals);
            sort($notes);
            $lines["{$qslug}"] = chordalDrop2($notes);
        }
        break;

    case 4:
        foreach (KEYS as $kslug => $koff) {
            $root = $base + $koff;
            foreach (QUALITIES as $qslug => $intervals) {
                $notes = array_map(fn ($i) => $root + $i, $intervals);
                sort($notes);
                $lines["{$kslug}-{$qslug}"] = chordalDrop2($notes);
            }
        }
        break;

    case 5:
        foreach (QUALITIES as $qslug => $intervals) {
            foreach (rotations($intervals, $base) as $pslug => $voicing) {
                $lines["{$qslug}-{$pslug}"] = $voicing;
            }
        }
        break;

    case 6:
        foreach (KEYS as $kslug => $koff) {
            $root = $base + $koff;
            foreach (QUALITIES_NO_SYMMETRIC as $qslug => $intervals) {
                foreach (rotations($intervals, $root) as $pslug => $voicing) {
                    $lines["{$kslug}-{$qslug}-{$pslug}"] = $voicing;
                }
            }
        }
        break;

    case 7:
        foreach (QUALITIES as $qslug => $intervals) {
            foreach (rotations($intervals, $base) as $pslug => $voicing) {
                $lines["{$qslug}-{$pslug}"] = chordalDrop2($voicing);
            }
        }
        break;

    case 8:
        foreach (KEYS as $kslug => $koff) {
            $root = $base + $koff;
            foreach (QUALITIES_NO_SYMMETRIC as $qslug => $intervals) {
                foreach (rotations($intervals, $root) as $pslug => $voicing) {
                    $lines["{$kslug}-{$qslug}-{$pslug}"] = chordalDrop2($voicing);
                }
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
