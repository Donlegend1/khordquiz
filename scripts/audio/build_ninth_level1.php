<?php

// Builds "9th Degree Chords · Level 1" — key of C only, root position, 6
// qualities (Major 9th, Minor 9th, Diminished 7(9), Dominant 9th, Minor
// 9(b5), Dominant 7(b9)), flat quality-only answer. The admin shows exactly
// these 6 rows — NOT padded/cycled up to the quiz's question_count (25);
// QuizController::buildQuestionSequence() already fair-shuffles a pool
// smaller than question_count to fill a real attempt. Matches 7th Degree
// Chords Level 1's shape, just with 5-note 9th chords in place of 7th
// chords. Rebuilds the quiz's questions and every instrument's attached
// audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const NINTH_L1_QUALITIES = ['Major 9th', 'Minor 9th', 'Diminished 7(9)', 'Dominant 9th', 'Minor 9(♭5)', 'Dominant 7(♭9)'];

const NINTH_L1_VARIANTS = [
    ['Major 9th', 'major9'],
    ['Minor 9th', 'minor9'],
    ['Diminished 7(9)', 'dim7add9'],
    ['Dominant 9th', 'dominant9'],
    ['Minor 9(♭5)', 'minor9b5'],
    ['Dominant 7(♭9)', 'dom7b9'],
];

const NINTH_L1_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

$quiz = Quiz::where('title', '9th Degree Chords · Level 1')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$created = 0;
$audioAttached = 0;
$position = 0;
foreach (NINTH_L1_VARIANTS as [$quality, $slug]) {
    $position++;

    $question = QuizQuestion::create([
        'quiz_id' => $quiz->id,
        'position' => $position,
        'prompt' => 'Listen to the chord and identify its quality.',
        'choices' => NINTH_L1_QUALITIES,
        'answer' => $quality,
    ]);

    foreach (NINTH_L1_INSTRUMENTS as $sound => $folderSlug) {
        QuizQuestionAudio::updateOrCreate(
            ['quiz_question_id' => $question->id, 'sound' => $sound],
            ['audio_path' => "ninth-degree-chords/level1/{$folderSlug}/{$slug}.wav"]
        );
        $audioAttached++;
    }
    $created++;
}

echo "Created {$created} rows on \"{$quiz->title}\", attached {$audioAttached} audio rows across ".count(NINTH_L1_INSTRUMENTS)." instruments.".PHP_EOL;
