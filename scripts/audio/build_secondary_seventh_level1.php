<?php

// Builds "Secondary 7th Chords · Level 1" — key of C only, root position, 6
// qualities (Augmented 7th, Minor-Major 7th, Diminished-Major 7th, Dominant
// 7(b5), Major 7(#5), Major 7(b5)), flat quality-only answer. The admin
// shows exactly these 6 rows — NOT padded/cycled up to the quiz's
// question_count (25); QuizController::buildQuestionSequence() already
// fair-shuffles a pool smaller than question_count to fill a real attempt.
// Matches 7th Degree Chords Level 1's shape. Rebuilds the quiz's questions
// and every instrument's attached audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const SEC7_L1_QUALITIES = ['Augmented 7th', 'Minor-Major 7th', 'Diminished-Major 7th', 'Dominant 7(♭5)', 'Major 7(♯5)', 'Major 7(♭5)'];

const SEC7_L1_VARIANTS = [
    ['Augmented 7th', 'aug7'],
    ['Minor-Major 7th', 'minmaj7'],
    ['Diminished-Major 7th', 'dimmaj7'],
    ['Dominant 7(♭5)', 'dom7b5'],
    ['Major 7(♯5)', 'maj7s5'],
    ['Major 7(♭5)', 'maj7b5'],
];

const SEC7_L1_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

$quiz = Quiz::where('title', 'Secondary 7th Chords · Level 1')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$created = 0;
$audioAttached = 0;
$position = 0;
foreach (SEC7_L1_VARIANTS as [$quality, $slug]) {
    $position++;

    $question = QuizQuestion::create([
        'quiz_id' => $quiz->id,
        'position' => $position,
        'prompt' => 'Listen to the chord and identify its quality.',
        'choices' => SEC7_L1_QUALITIES,
        'answer' => $quality,
    ]);

    foreach (SEC7_L1_INSTRUMENTS as $sound => $folderSlug) {
        QuizQuestionAudio::updateOrCreate(
            ['quiz_question_id' => $question->id, 'sound' => $sound],
            ['audio_path' => "secondary-seventh-chords/level1/{$folderSlug}/{$slug}.wav"]
        );
        $audioAttached++;
    }
    $created++;
}

echo "Created {$created} rows on \"{$quiz->title}\", attached {$audioAttached} audio rows across ".count(SEC7_L1_INSTRUMENTS)." instruments.".PHP_EOL;
