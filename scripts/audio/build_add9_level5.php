<?php

// Builds "Add 9 · Level 5" — same shape as Level 1 (key of C only, 4
// qualities, flat quality-only answer), but every chord is voiced in
// DROP-2 form (2nd-from-top note dropped an octave — chordalDrop2() in
// chordal_melodies_pool.php, same transform Basic Triads Level 3 used
// relative to its Level 1) based on C4 (MIDI 60) instead of Level 1's
// close-position C3 — same relationship Basic Triads Level 3 has to Level
// 1. A drop-2'd chord's quality is unchanged — only the voicing/register
// differs. The admin shows exactly these 4 rows — NOT padded/cycled up to
// the quiz's question_count (25); QuizController::buildQuestionSequence()
// already fair-shuffles a pool smaller than question_count to fill a real
// attempt. Rebuilds the quiz's questions and every instrument's attached
// audio from scratch every run.
//
// NOTE: this quiz was originally built as "Add 9 · Level 4" before the user
// corrected the level numbering — Level 4 is actually Level 3's content
// (quality+position) across all 12 keys (see build_add9_level4.php), and
// this drop-2 content moved to a new Level 5 quiz (id changes, but the
// storage folder/audio paths were renamed add9/level4 -> add9/level5 to
// match).

require __DIR__.'/chordal_melodies_pool.php';

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const ADD9_L5_QUALITIES = ['Major add 9', 'Minor add 9', 'Diminished add 9', 'Augmented add 9'];

const ADD9_L5_SLUGS = [
    'Major add 9' => 'major',
    'Minor add 9' => 'minor',
    'Diminished add 9' => 'diminished',
    'Augmented add 9' => 'augmented',
];

const ADD9_L5_INTERVALS = [
    'major' => [0, 2, 4, 7], 'minor' => [0, 2, 3, 7],
    'diminished' => [0, 2, 3, 6], 'augmented' => [0, 2, 4, 8],
];

const ADD9_L5_BASE = 60; // C4

// sound => storage folder slug, matching Levels 1-4's naming.
const ADD9_L5_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

$quiz = Quiz::where('title', 'Add 9 · Level 5')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$created = 0;
$audioAttached = 0;
$position = 0;
foreach (ADD9_L5_QUALITIES as $quality) {
    $position++;
    $slug = ADD9_L5_SLUGS[$quality];

    $question = QuizQuestion::create([
        'quiz_id' => $quiz->id,
        'position' => $position,
        'prompt' => 'Listen to the chord and identify its quality.',
        'choices' => ADD9_L5_QUALITIES,
        'answer' => $quality,
    ]);

    foreach (ADD9_L5_INSTRUMENTS as $sound => $folderSlug) {
        QuizQuestionAudio::updateOrCreate(
            ['quiz_question_id' => $question->id, 'sound' => $sound],
            ['audio_path' => "add9/level5/{$folderSlug}/{$slug}.wav"]
        );
        $audioAttached++;
    }
    $created++;
}

// Sanity check the rendered files actually match chordalDrop2() — logged, not enforced.
foreach (ADD9_L5_INTERVALS as $slug => $intervals) {
    $voicing = array_map(fn ($i) => ADD9_L5_BASE + $i, $intervals);
    $drop = chordalDrop2($voicing);
    echo "  {$slug}: root position ".implode(',', $voicing).' -> drop-2 '.implode(',', $drop).PHP_EOL;
}

echo "Created {$created} rows on \"{$quiz->title}\", attached {$audioAttached} audio rows across ".count(ADD9_L5_INSTRUMENTS)." instruments.".PHP_EOL;
