<?php

// Builds "Add 9 · Level 4" — same shape as Level 3 (4 qualities x 4 rotated
// positions for audio variety, flat quality-only answer — NOT compound
// quality+position, per explicit instruction that Add 9 never asks the
// member to identify an inversion/position), but across ALL 12 keys instead
// of just C. 12 keys x 4 qualities x 4 positions = 192 rows — MORE than the
// quiz's question_count (25), same pool-larger-than-question_count pattern
// as Basic Triads Level 6: QuizController::buildQuestionSequence() fair-
// shuffles a random 25 per attempt instead of hard-cycling a fixed subset.
// Position built by ROTATION (see build_add9_level3.php) — position N takes
// the voicing's first N notes and moves each up an octave. Rebuilds the
// quiz's questions and every instrument's attached audio from scratch every
// run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const ADD9_L4_QUALITIES = ['Major add 9', 'Minor add 9', 'Diminished add 9', 'Augmented add 9'];

const ADD9_L4_POSITION_LABELS = ['root' => 'Root Position', '1st' => '1st Inversion', '2nd' => '2nd Inversion', '3rd' => '3rd Inversion'];

// quality => close-position intervals from root (root, 9th-as-2nd, 3rd, 5th).
const ADD9_L4_QUALITY_INTERVALS = [
    'Major add 9' => [0, 2, 4, 7],
    'Minor add 9' => [0, 2, 3, 7],
    'Diminished add 9' => [0, 2, 3, 6],
    'Augmented add 9' => [0, 2, 4, 8],
];

// key slug => semitone offset from C3 (MIDI 48), chromatically through B3.
const ADD9_L4_KEYS = [
    'c' => 0, 'db' => 1, 'd' => 2, 'eb' => 3, 'e' => 4, 'f' => 5,
    'gb' => 6, 'g' => 7, 'ab' => 8, 'a' => 9, 'bb' => 10, 'b' => 11,
];

const ADD9_L4_BASE = 48; // C3

// sound => storage folder slug, matching Levels 1-3's naming.
const ADD9_L4_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

function add9L4Positions(array $intervals, int $root): array
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

$quiz = Quiz::where('title', 'Add 9 · Level 4')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (ADD9_L4_KEYS as $keySlug => $keyOffset) {
    $root = ADD9_L4_BASE + $keyOffset;

    foreach (ADD9_L4_QUALITY_INTERVALS as $qualityLabel => $intervals) {
        $qualitySlug = strtolower(explode(' ', $qualityLabel)[0]);
        $positions = add9L4Positions($intervals, $root);

        foreach (ADD9_L4_POSITION_LABELS as $posSlug => $posLabel) {
            $position++;

            $question = QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'position' => $position,
                'prompt' => 'Listen to the chord and identify its quality.',
                'choices' => ADD9_L4_QUALITIES,
                'answer' => $qualityLabel,
            ]);

            foreach (ADD9_L4_INSTRUMENTS as $sound => $folderSlug) {
                QuizQuestionAudio::updateOrCreate(
                    ['quiz_question_id' => $question->id, 'sound' => $sound],
                    ['audio_path' => "add9/level4/{$folderSlug}/{$keySlug}-{$qualitySlug}-{$posSlug}.wav"]
                );
                $audioAttached++;
            }
            $created++;
        }
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of ".count(ADD9_L4_KEYS)." keys x 4 qualities x 4 positions; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(ADD9_L4_INSTRUMENTS)." instruments.".PHP_EOL;
