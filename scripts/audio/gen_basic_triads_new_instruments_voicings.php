<?php

// Voicing generator for adding Trombone, Saxophone, Marimba, Celesta, and
// Vibraphone to Basic Triads Levels 1-8 — reuses the EXACT voicing logic
// already established for this category (standard triad inversion
// technique, chordalDrop2 for drop-2 levels, same quality/key/inversion
// restrictions per level as the original build scripts).
//
// Usage: gen_basic_triads_new_instruments_voicings.php <level 1-8> <base MIDI root>
// Prints one line per chord: <path-suffix>\t<comma-separated MIDI notes>

require __DIR__.'/chordal_melodies_pool.php';

const QUALITY_INTERVALS = [
    'major' => [0, 4, 7], 'minor' => [0, 3, 7], 'diminished' => [0, 3, 6],
    'augmented' => [0, 4, 8], 'sus4' => [0, 5, 7], 'sus2' => [0, 2, 7],
];

// Level 5/7 quality slug => interval (Suspended = sus2 only, not split).
const L5_QUALITIES = [
    'major' => [0, 4, 7], 'minor' => [0, 3, 7], 'diminished' => [0, 3, 6],
    'augmented' => [0, 4, 8], 'suspended' => [0, 2, 7],
];

// Level 6/8 quality slug => interval (Major/Minor/Diminished only).
const L6_QUALITIES = ['major' => [0, 4, 7], 'minor' => [0, 3, 7], 'diminished' => [0, 3, 6]];

const KEYS_12 = ['c', 'db', 'd', 'eb', 'e', 'f', 'gb', 'g', 'ab', 'a', 'bb', 'b'];

function inversions(int $root, array $intervals): array
{
    $notes = array_map(fn ($i) => $root + $i, $intervals);
    sort($notes);
    [$n0, $n1, $n2] = $notes;

    return ['root' => [$n0, $n1, $n2], '1st' => [$n1, $n2, $n0 + 12], '2nd' => [$n2, $n0 + 12, $n1 + 12]];
}

$level = (int) $argv[1];
$base = (int) $argv[2];

$lines = [];

switch ($level) {
    case 1:
        foreach (QUALITY_INTERVALS as $slug => $intervals) {
            $notes = array_map(fn ($i) => $base + $i, $intervals);
            sort($notes);
            $lines[$slug] = $notes;
        }
        break;

    case 2:
        foreach (KEYS_12 as $ki => $kslug) {
            $root = $base + $ki;
            foreach (QUALITY_INTERVALS as $slug => $intervals) {
                $notes = array_map(fn ($i) => $root + $i, $intervals);
                sort($notes);
                $lines["{$kslug}-{$slug}"] = $notes;
            }
        }
        break;

    case 3:
        foreach (QUALITY_INTERVALS as $slug => $intervals) {
            $notes = array_map(fn ($i) => $base + $i, $intervals);
            sort($notes);
            $lines[$slug] = chordalDrop2($notes);
        }
        break;

    case 4:
        foreach (KEYS_12 as $ki => $kslug) {
            $root = $base + $ki;
            foreach (QUALITY_INTERVALS as $slug => $intervals) {
                $notes = array_map(fn ($i) => $root + $i, $intervals);
                sort($notes);
                $lines["{$kslug}-{$slug}"] = chordalDrop2($notes);
            }
        }
        break;

    case 5:
        foreach (L5_QUALITIES as $qslug => $intervals) {
            foreach (inversions($base, $intervals) as $islug => $voicing) {
                $lines["{$qslug}-{$islug}"] = $voicing;
            }
        }
        break;

    case 6:
        foreach (KEYS_12 as $ki => $kslug) {
            $root = $base + $ki;
            foreach (L6_QUALITIES as $qslug => $intervals) {
                foreach (inversions($root, $intervals) as $islug => $voicing) {
                    $lines["{$kslug}-{$qslug}-{$islug}"] = $voicing;
                }
            }
        }
        break;

    case 7:
        foreach (L5_QUALITIES as $qslug => $intervals) {
            foreach (inversions($base, $intervals) as $islug => $voicing) {
                $lines["{$qslug}-{$islug}"] = chordalDrop2($voicing);
            }
        }
        break;

    case 8:
        foreach (KEYS_12 as $ki => $kslug) {
            $root = $base + $ki;
            foreach (L6_QUALITIES as $qslug => $intervals) {
                foreach (inversions($root, $intervals) as $islug => $voicing) {
                    $lines["{$kslug}-{$qslug}-{$islug}"] = chordalDrop2($voicing);
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
