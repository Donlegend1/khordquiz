<?php

// Voicing + answer generator for "Chord naming" — real content replacing the
// 5-row quality-only placeholder. Builds real MIDI voicings for a curated
// set of quality + degree(s) + exclude combinations (web's own 14-item
// NAMING_DEGREES pool, not the app's segmented 8-item one — see the
// AskUserQuestion confirmation), and the exact pipe-delimited answer string
// resources/js/lib/quizAnswerPresets.js's encodeNamingAnswer() expects
// ($data['choice'] === $question->answer comparison in QuizController).
//
// Usage: gen_chord_naming_voicings.php <base MIDI root>
// Prints one line per combo: <slug>\t<answer>\t<comma-separated MIDI notes>

// Canonical order — must match NAMING_DEGREES in quizAnswerPresets.js
// exactly, since both sides sort degrees into this order before joining.
const NAMING_DEGREES_ORDER = ['♭5', '6', '7', '9', '♭9', '♯9', '11', '♭11', '♯11', '13', '♭13', '♯13', 'Sus 2', 'Sus 4'];

const DEGREE_SLUG = [
    '♭5' => 'b5', '6' => '6', '7' => '7', '9' => '9', '♭9' => 'b9', '♯9' => 's9',
    '11' => '11', '♭11' => 'b11', '♯11' => 's11', '13' => '13', '♭13' => 'b13', '♯13' => 's13',
    'Sus 2' => 'sus2', 'Sus 4' => 'sus4',
];
const EXCLUDE_SLUG = ['No 3' => 'no3', 'No 5' => 'no5', 'None' => 'none'];

const QUALITY_SHAPE = [
    // third, fifth, seventh-interval-if-'7'-picked (Dominant's 7 is baked in, not picked)
    'Major' => ['third' => 4, 'fifth' => 7, 'seventh' => 11],
    'Minor' => ['third' => 3, 'fifth' => 7, 'seventh' => 10],
    'Augmented' => ['third' => 4, 'fifth' => 8, 'seventh' => 11],
    'Dominant' => ['third' => 4, 'fifth' => 7, 'seventh' => 10],
    'Diminished' => ['third' => 3, 'fifth' => 6, 'seventh' => 9],
];

const DEGREE_INTERVAL = [
    '6' => 9, '9' => 14, '♭9' => 13, '♯9' => 15, '11' => 17, '♭11' => 16,
    '♯11' => 18, '13' => 21, '♭13' => 20, '♯13' => 22,
];

// 10 curated combos per quality — a mix of bare/7th/extension/sus/exclude
// cases, not an exhaustive combinatorial sweep (per the ~45-50 row scope
// confirmed with the user). [degrees[], exclude]
const VARIANTS = [
    'Major' => [
        [[], 'None'], [['7'], 'None'], [['9'], 'None'], [['7', '9'], 'None'], [['7', '♭9'], 'None'],
        [['6'], 'None'], [['Sus 2'], 'None'], [['Sus 4'], 'None'], [['7'], 'No 3'], [['7'], 'No 5'],
    ],
    'Minor' => [
        [[], 'None'], [['7'], 'None'], [['9'], 'None'], [['7', '9'], 'None'], [['7', '♭9'], 'None'],
        [['6'], 'None'], [['Sus 2'], 'None'], [['Sus 4'], 'None'], [['7'], 'No 3'], [['7'], 'No 5'],
    ],
    'Augmented' => [
        [[], 'None'], [['7'], 'None'], [['9'], 'None'], [['7', '9'], 'None'], [['6'], 'None'],
        [['Sus 2'], 'None'], [['Sus 4'], 'None'], [['7'], 'No 3'], [['7'], 'No 5'], [['♯11'], 'None'],
    ],
    'Diminished' => [
        [[], 'None'], [['7'], 'None'], [['9'], 'None'], [['7', '9'], 'None'], [['♭13'], 'None'],
        [['Sus 2'], 'None'], [['Sus 4'], 'None'], [['7'], 'No 3'], [['7'], 'No 5'], [['♯11'], 'None'],
    ],
    // Dominant already has its 7th baked in (baseChord-style) — '7' is never
    // one of its own distinguishing picks.
    'Dominant' => [
        [[], 'None'], [['9'], 'None'], [['♭9'], 'None'], [['♯9'], 'None'], [['13'], 'None'],
        [['♭13'], 'None'], [['♯11'], 'None'], [['Sus 4'], 'None'], [['9'], 'No 3'], [[], 'No 5'],
    ],
];

function encodeAnswer(string $quality, array $degrees, string $exclude): string
{
    $sorted = array_values(array_filter(NAMING_DEGREES_ORDER, fn ($d) => in_array($d, $degrees, true)));

    return "{$quality}|".implode(',', $sorted)."|{$exclude}";
}

function buildVoicing(string $quality, array $degrees, string $exclude, int $base): array
{
    $shape = QUALITY_SHAPE[$quality];
    $third = $shape['third'];
    $fifth = $shape['fifth'];

    if (in_array('Sus 2', $degrees, true)) {
        $third = 2;
    } elseif (in_array('Sus 4', $degrees, true)) {
        $third = 5;
    }
    if (in_array('♭5', $degrees, true)) {
        $fifth = 6;
    }

    $intervals = [0];
    if ($exclude !== 'No 3') {
        $intervals[] = $third;
    }
    if ($exclude !== 'No 5') {
        $intervals[] = $fifth;
    }

    $hasSeventh = in_array('7', $degrees, true) || $quality === 'Dominant';
    if ($hasSeventh) {
        $intervals[] = $shape['seventh'];
    }

    foreach ($degrees as $degree) {
        if (isset(DEGREE_INTERVAL[$degree]) && ! in_array(DEGREE_INTERVAL[$degree], $intervals, true)) {
            $intervals[] = DEGREE_INTERVAL[$degree];
        }
    }

    $intervals = array_values(array_unique($intervals));
    sort($intervals);

    return array_map(fn ($i) => $base + $i, $intervals);
}

$base = (int) $argv[1];

foreach (VARIANTS as $quality => $variants) {
    foreach ($variants as $i => [$degrees, $exclude]) {
        $notes = buildVoicing($quality, $degrees, $exclude, $base);
        $answer = encodeAnswer($quality, $degrees, $exclude);
        $degreeSlugs = implode('-', array_map(fn ($d) => DEGREE_SLUG[$d], $degrees)) ?: 'bare';
        $slug = strtolower($quality).'-'.$degreeSlugs.'-'.EXCLUDE_SLUG[$exclude].'-'.$i;
        echo "{$slug}\t{$answer}\t".implode(',', $notes).PHP_EOL;
    }
}
