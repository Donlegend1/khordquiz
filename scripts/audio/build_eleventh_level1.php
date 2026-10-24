<?php

// Builds "11th Degree Chords · Level 1" — key of C only, root position, 6
// qualities (Major 9(#11), Minor 11th, Diminished 7(9,11), Dominant
// 9(#11), Minor 11(b5), Dominant 7(b9,#11)), flat quality-only answer. The
// admin shows exactly these 6 rows — NOT padded/cycled up to
// question_count (25); QuizController::buildQuestionSequence() already
// fair-shuffles a pool smaller than question_count to fill a real attempt.
// Rebuilds the quiz's questions and every instrument's attached audio from
// scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const ELEVENTH_L1_QUALITIES = ['Major 9(♯11)', 'Minor 11th', 'Diminished 7(9,11)', 'Dominant 9(♯11)', 'Minor 11(♭5)', 'Dominant 7(♭9,♯11)'];

const ELEVENTH_L1_SLUGS = [
    'Major 9(♯11)' => 'maj9s11', 'Minor 11th' => 'minor11', 'Diminished 7(9,11)' => 'dim7911',
    'Dominant 9(♯11)' => 'dom9s11', 'Minor 11(♭5)' => 'minor11b5', 'Dominant 7(♭9,♯11)' => 'dom7b9s11',
];

const ELEVENTH_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', '11th Degree Chords · Level 1')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$created = 0;
$audioAttached = 0;
$position = 0;
foreach (ELEVENTH_L1_QUALITIES as $quality) {
    $position++;
    $slug = ELEVENTH_L1_SLUGS[$quality];

    $question = QuizQuestion::create([
        'quiz_id' => $quiz->id,
        'position' => $position,
        'prompt' => 'Listen to the chord and identify its quality.',
        'choices' => ELEVENTH_L1_QUALITIES,
        'answer' => $quality,
    ]);

    foreach (ELEVENTH_INSTRUMENTS as $sound => $folderSlug) {
        QuizQuestionAudio::updateOrCreate(
            ['quiz_question_id' => $question->id, 'sound' => $sound],
            ['audio_path' => "eleventh-degree-chords/level1/{$folderSlug}/{$slug}.wav"]
        );
        $audioAttached++;
    }
    $created++;
}

echo "Created {$created} rows on \"{$quiz->title}\", attached {$audioAttached} audio rows across ".count(ELEVENTH_INSTRUMENTS)." instruments.".PHP_EOL;
