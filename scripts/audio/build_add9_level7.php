<?php

// Builds "Add 9 · Level 7" — same shape as Level 3 (key of C only, 4
// qualities x 4 rotated positions for audio variety, flat quality-only
// answer — NOT compound quality+position, per explicit instruction), but
// every position is additionally voiced in DROP-2 form (2nd-from-top note
// dropped an octave — chordalDrop2() in chordal_melodies_pool.php, applied
// AFTER the position rotation) based on C4 (MIDI 60) instead of Level 3's
// close-position C3 — same relationship Basic Triads Level 7 has to Level 5.
// 4 qualities x 4 positions = 16 rows. Rebuilds the quiz's questions and
// every instrument's attached audio from scratch every run.

require __DIR__.'/chordal_melodies_pool.php';

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const ADD9_L7_QUALITIES = ['Major add 9', 'Minor add 9', 'Diminished add 9', 'Augmented add 9'];

const ADD9_L7_POSITION_LABELS = ['root' => 'Root Position', '1st' => '1st Inversion', '2nd' => '2nd Inversion', '3rd' => '3rd Inversion'];

const ADD9_L7_QUALITY_INTERVALS = [
    'Major add 9' => [0, 2, 4, 7],
    'Minor add 9' => [0, 2, 3, 7],
    'Diminished add 9' => [0, 2, 3, 6],
    'Augmented add 9' => [0, 2, 4, 8],
];

const ADD9_L7_BASE = 60; // C4

// sound => storage folder slug, matching Levels 1-6's naming.
const ADD9_L7_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

function add9L7Rotations(array $intervals, int $root): array
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

$quiz = Quiz::where('title', 'Add 9 · Level 7')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (ADD9_L7_QUALITY_INTERVALS as $qualityLabel => $intervals) {
    $qualitySlug = strtolower(explode(' ', $qualityLabel)[0]);
    $rotations = add9L7Rotations($intervals, ADD9_L7_BASE);

    foreach (ADD9_L7_POSITION_LABELS as $posSlug => $posLabel) {
        $position++;

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'position' => $position,
            'prompt' => 'Listen to the chord and identify its quality.',
            'choices' => ADD9_L7_QUALITIES,
            'answer' => $qualityLabel,
        ]);

        foreach (ADD9_L7_INSTRUMENTS as $sound => $folderSlug) {
            QuizQuestionAudio::updateOrCreate(
                ['quiz_question_id' => $question->id, 'sound' => $sound],
                ['audio_path' => "add9/level7/{$folderSlug}/{$qualitySlug}-{$posSlug}.wav"]
            );
            $audioAttached++;
        }
        $created++;
    }
}

// Sanity check the rendered files actually match chordalDrop2() — logged, not enforced.
foreach (ADD9_L7_QUALITY_INTERVALS as $qualityLabel => $intervals) {
    $qualitySlug = strtolower(explode(' ', $qualityLabel)[0]);
    $rotations = add9L7Rotations($intervals, ADD9_L7_BASE);
    foreach ($rotations as $posSlug => $voicing) {
        $drop = chordalDrop2($voicing);
        echo "  {$qualitySlug}-{$posSlug}: rotation ".implode(',', $voicing).' -> drop-2 '.implode(',', $drop).PHP_EOL;
    }
}

echo "Created {$created} rows on \"{$quiz->title}\", attached {$audioAttached} audio rows across ".count(ADD9_L7_INSTRUMENTS)." instruments.".PHP_EOL;
