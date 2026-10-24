<?php

// Builds "7th Degree Chords · Level 1" — key of C only, root position, 5
// qualities (Diminished 7th, Dominant 7th, Minor 7(b5), Major 7th, Minor
// 7th), flat quality-only answer. The admin shows exactly these 5 rows —
// NOT padded/cycled up to the quiz's question_count (25);
// QuizController::buildQuestionSequence() already fair-shuffles a pool
// smaller than question_count to fill a real attempt. Matches Basic Triads
// Level 1's shape exactly, just with 7th chords (4 notes) in place of
// triads. Rebuilds the quiz's questions and every instrument's attached
// audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const SEVENTH_L1_QUALITIES = ['Diminished 7th', 'Dominant 7th', 'Minor 7(♭5)', 'Major 7th', 'Minor 7th'];

// [answer label, audio file slug] — cycled in this order across the rows.
const SEVENTH_L1_VARIANTS = [
    ['Diminished 7th', 'diminished7'],
    ['Dominant 7th', 'dominant7'],
    ['Minor 7(♭5)', 'm7b5'],
    ['Major 7th', 'major7'],
    ['Minor 7th', 'minor7'],
];

const SEVENTH_L1_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

$quiz = Quiz::where('title', '7th Degree Chords · Level 1')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$created = 0;
$audioAttached = 0;
$position = 0;
foreach (SEVENTH_L1_VARIANTS as [$quality, $slug]) {
    $position++;

    $question = QuizQuestion::create([
        'quiz_id' => $quiz->id,
        'position' => $position,
        'prompt' => 'Listen to the chord and identify its quality.',
        'choices' => SEVENTH_L1_QUALITIES,
        'answer' => $quality,
    ]);

    foreach (SEVENTH_L1_INSTRUMENTS as $sound => $folderSlug) {
        QuizQuestionAudio::updateOrCreate(
            ['quiz_question_id' => $question->id, 'sound' => $sound],
            ['audio_path' => "seventh-degree-chords/level1/{$folderSlug}/{$slug}.wav"]
        );
        $audioAttached++;
    }
    $created++;
}

echo "Created {$created} rows on \"{$quiz->title}\", attached {$audioAttached} audio rows across ".count(SEVENTH_L1_INSTRUMENTS)." instruments.".PHP_EOL;
