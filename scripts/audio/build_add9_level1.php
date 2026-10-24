<?php

// Builds "Add 9 · Level 1" — 4 distinct Piano audios (Major, Minor,
// Diminished, Augmented add9 chords, root position, key of C, from C3/MIDI
// 48). Voicing is CLOSE position with the 9th brought down into the same
// octave as a 2nd (not spread an octave above the root like a jazz "add9"
// voicing) — e.g. Major add9 = C D E G (intervals [0,2,4,7]) — per explicit
// instruction. The admin shows exactly these 4 rows — NOT padded/cycled up
// to the quiz's question_count (25); QuizController::buildQuestionSequence()
// already fair-shuffles a pool smaller than question_count to fill a real
// attempt. Rebuilds the quiz's questions and every instrument's attached
// audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const ADD9_L1_QUALITIES = ['Major add 9', 'Minor add 9', 'Diminished add 9', 'Augmented add 9'];

// quality => audio file slug (matches storage/app/public/add9/{instrument}/{slug}.wav)
const ADD9_L1_SLUGS = [
    'Major add 9' => 'major',
    'Minor add 9' => 'minor',
    'Diminished add 9' => 'diminished',
    'Augmented add 9' => 'augmented',
];

// sound => storage folder slug, matching Basic Triads' instrument naming.
const ADD9_L1_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

$quiz = Quiz::where('title', 'Add 9 · Level 1')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$created = 0;
$audioAttached = 0;
$position = 0;
foreach (ADD9_L1_QUALITIES as $quality) {
    $position++;
    $slug = ADD9_L1_SLUGS[$quality];

    $question = QuizQuestion::create([
        'quiz_id' => $quiz->id,
        'position' => $position,
        'prompt' => 'Listen to the chord and identify its quality.',
        'choices' => ADD9_L1_QUALITIES,
        'answer' => $quality,
    ]);

    foreach (ADD9_L1_INSTRUMENTS as $sound => $folderSlug) {
        QuizQuestionAudio::updateOrCreate(
            ['quiz_question_id' => $question->id, 'sound' => $sound],
            ['audio_path' => "add9/{$folderSlug}/{$slug}.wav"]
        );
        $audioAttached++;
    }
    $created++;
}

echo "Created {$created} rows on \"{$quiz->title}\", attached {$audioAttached} audio rows across ".count(ADD9_L1_INSTRUMENTS)." instruments.".PHP_EOL;
