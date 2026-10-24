<?php

// Builds "Add 9 · Level 3" — key of C only, flat quality-only answer (NOT
// compound quality+position — per explicit instruction, Add 9 never asks
// the member to pick an inversion/position, that's Basic Triads' thing).
// Still renders/plays all 4 positions (root/1st/2nd/3rd) per quality for
// audio variety/difficulty — the member just isn't asked to identify which
// one, only the quality, same answer shape as Level 1. Position is built by
// ROTATION (not the standard triad-inversion technique): position N takes
// the voicing's first N notes and moves each up an octave, e.g. Major add9
// root [C,D,E,G] -> 1st [D,E,G,C'] -> 2nd [E,G,C',D'] -> 3rd [G,C',D',E'].
// 4 qualities x 4 positions = 16 rows (less than question_count=25, same
// "pool smaller than question_count, repeats across laps" pattern as Basic
// Triads Level 5). Rebuilds the quiz's questions and every instrument's
// attached audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const ADD9_L3_QUALITIES = ['Major add 9', 'Minor add 9', 'Diminished add 9', 'Augmented add 9'];

const ADD9_L3_POSITION_LABELS = ['root' => 'Root Position', '1st' => '1st Inversion', '2nd' => '2nd Inversion', '3rd' => '3rd Inversion'];

// quality => close-position intervals from root (root, 9th-as-2nd, 3rd, 5th).
const ADD9_L3_INTERVALS = [
    'Major add 9' => [0, 2, 4, 7],
    'Minor add 9' => [0, 2, 3, 7],
    'Diminished add 9' => [0, 2, 3, 6],
    'Augmented add 9' => [0, 2, 4, 8],
];

const ADD9_L3_BASE = 48; // C3

// sound => storage folder slug, matching Levels 1-2's naming.
const ADD9_L3_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

function add9L3Positions(array $intervals, int $root): array
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

$quiz = Quiz::where('title', 'Add 9 · Level 3')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (ADD9_L3_INTERVALS as $qualityLabel => $intervals) {
    $qualitySlug = strtolower(explode(' ', $qualityLabel)[0]);
    $positions = add9L3Positions($intervals, ADD9_L3_BASE);

    foreach (ADD9_L3_POSITION_LABELS as $posSlug => $posLabel) {
        $position++;

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'position' => $position,
            'prompt' => 'Listen to the chord and identify its quality.',
            'choices' => ADD9_L3_QUALITIES,
            'answer' => $qualityLabel,
        ]);

        foreach (ADD9_L3_INSTRUMENTS as $sound => $folderSlug) {
            QuizQuestionAudio::updateOrCreate(
                ['quiz_question_id' => $question->id, 'sound' => $sound],
                ['audio_path' => "add9/level3/{$folderSlug}/{$qualitySlug}-{$posSlug}.wav"]
            );
            $audioAttached++;
        }
        $created++;
    }
}

echo "Created {$created} rows on \"{$quiz->title}\", attached {$audioAttached} audio rows across ".count(ADD9_L3_INSTRUMENTS)." instruments.".PHP_EOL;
