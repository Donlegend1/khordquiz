<?php

// Voicing generator for "Secondary 11th Chords" Levels 1-6 — mirrors 11th
// Degree Chords' exact structure and techniques, just with this category's
// own 4 qualities:
//   Level 1: C only, root position.
//   Level 2: 12 keys, root position.
//   Level 3: C only, fixed root-position triad left hand + inverted
//     upper-triad/7th right hand (2+3 = 5 variants per quality).
//   Level 4: 12-key version of Level 3.
//   Level 5: C only, "avoid the root" (left triad/7th x right triad/7th,
//     both hands inverted together, 2 inversions each = 8 variants).
//   Level 6: 12-key version of Level 5.
//
// Usage: gen_secondary_eleventh_voicings.php <level 1-6> <base MIDI root for C>
// Prints one line per chord: <slug>\t<comma-separated MIDI notes>

const QUALITIES = [
    'maj9s5s11' => [0, 4, 8, 11, 14, 18],   // Major 9(#5,#11)
    'dim7b911' => [0, 3, 6, 9, 13, 17],     // Diminished 7(b9,11)
    'minmaj11' => [0, 3, 7, 11, 14, 17],    // Minor-Major 11th
    'aug9s11' => [0, 4, 8, 10, 14, 18],     // Augmented 9(#11)
];

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

$level = (int) $argv[1];
$cBase = (int) $argv[2];

$lines = [];

function level3Variants(array $intervals, int $base): array
{
    [$i0, $i1, $i2, $i3, $i4, $i5] = $intervals;
    $left = [$base + $i0, $base + $i1, $base + $i2];
    $upperTriad = [$base + $i3, $base + $i4, $base + $i5];
    $upperSeventh = [$base + $i2, $base + $i3, $base + $i4, $base + $i5];

    $out = [];
    foreach ([1, 2] as $inv) {
        $voicing = array_merge($left, rotation($upperTriad, $inv));
        sort($voicing);
        $out["pAv{$inv}"] = $voicing;
    }
    foreach ([1, 2, 3] as $inv) {
        $voicing = array_merge($left, rotation($upperSeventh, $inv));
        sort($voicing);
        $out["pBv{$inv}"] = $voicing;
    }

    return $out;
}

function level5Variants(array $intervals, int $base): array
{
    [$i0, $i1, $i2, $i3, $i4, $i5] = $intervals;
    $leftTriad = [$base + $i0, $base + $i1, $base + $i2];
    $leftSeventh = [$base + $i0, $base + $i1, $base + $i2, $base + $i3];
    $rightTriad = [$base + $i3, $base + $i4, $base + $i5];
    $rightSeventh = [$base + $i2, $base + $i3, $base + $i4, $base + $i5];

    $combos = ['tt' => [$leftTriad, $rightTriad], 'ts' => [$leftTriad, $rightSeventh],
        'st' => [$leftSeventh, $rightTriad], 'ss' => [$leftSeventh, $rightSeventh]];

    $out = [];
    foreach ($combos as $comboSlug => [$left, $right]) {
        foreach ([1, 2] as $inv) {
            $voicing = array_merge(rotation($left, $inv), rotation($right, $inv));
            sort($voicing);
            $out["{$comboSlug}{$inv}"] = $voicing;
        }
    }

    return $out;
}

switch ($level) {
    case 1:
        foreach (QUALITIES as $qslug => $intervals) {
            $notes = array_map(fn ($i) => $cBase + $i, $intervals);
            sort($notes);
            $lines[$qslug] = $notes;
        }
        break;

    case 2:
        foreach (KEYS as $kslug => $koff) {
            $root = $cBase + $koff;
            foreach (QUALITIES as $qslug => $intervals) {
                $notes = array_map(fn ($i) => $root + $i, $intervals);
                sort($notes);
                $lines["{$kslug}-{$qslug}"] = $notes;
            }
        }
        break;

    case 3:
        foreach (QUALITIES as $qslug => $intervals) {
            foreach (level3Variants($intervals, $cBase) as $vslug => $voicing) {
                $lines["{$qslug}-{$vslug}"] = $voicing;
            }
        }
        break;

    case 4:
        foreach (KEYS as $kslug => $koff) {
            $root = $cBase + $koff;
            foreach (QUALITIES as $qslug => $intervals) {
                foreach (level3Variants($intervals, $root) as $vslug => $voicing) {
                    $lines["{$kslug}-{$qslug}-{$vslug}"] = $voicing;
                }
            }
        }
        break;

    case 5:
        foreach (QUALITIES as $qslug => $intervals) {
            foreach (level5Variants($intervals, $cBase) as $vslug => $voicing) {
                $lines["{$qslug}-{$vslug}"] = $voicing;
            }
        }
        break;

    case 6:
        foreach (KEYS as $kslug => $koff) {
            $root = $cBase + $koff;
            foreach (QUALITIES as $qslug => $intervals) {
                foreach (level5Variants($intervals, $root) as $vslug => $voicing) {
                    $lines["{$kslug}-{$qslug}-{$vslug}"] = $voicing;
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
