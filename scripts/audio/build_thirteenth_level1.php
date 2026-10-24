<?php

// Builds "13th Degree Chords · Level 1" — key of C only, root position, 4
// qualities (Major 13(#11), Minor 13th, Dominant 13(#11), Dominant
// 13(b9,#11), each a 7-note chord: root/3rd/5th/7th/9th/11th/13th), flat
// quality-only answer. The admin shows exactly these 4 rows — NOT padded/
// cycled up to question_count (25); QuizController::buildQuestionSequence()
// already fair-shuffles a pool smaller than question_count to fill a real
// attempt. Rebuilds the quiz's questions and every instrument's attached
// audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const THIRTEENTH_L1_QUALITIES = ['Major 13(♯11)', 'Minor 13th', 'Dominant 13(♯11)', 'Dominant 13(♭9,♯11)'];

const THIRTEENTH_L1_SLUGS = [
    'Major 13(♯11)' => 'maj13s11', 'Minor 13th' => 'minor13',
    'Dominant 13(♯11)' => 'dom13s11', 'Dominant 13(♭9,♯11)' => 'dom13b9s11',
];

const THIRTEENTH_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', '13th Degree Chords · Level 1')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$created = 0;
$audioAttached = 0;
$position = 0;
foreach (THIRTEENTH_L1_QUALITIES as $quality) {
    $position++;
    $slug = THIRTEENTH_L1_SLUGS[$quality];

    $question = QuizQuestion::create([
        'quiz_id' => $quiz->id,
        'position' => $position,
        'prompt' => 'Listen to the chord and identify its quality.',
        'choices' => THIRTEENTH_L1_QUALITIES,
        'answer' => $quality,
    ]);

    foreach (THIRTEENTH_INSTRUMENTS as $sound => $folderSlug) {
        QuizQuestionAudio::updateOrCreate(
            ['quiz_question_id' => $question->id, 'sound' => $sound],
            ['audio_path' => "thirteenth-degree-chords/level1/{$folderSlug}/{$slug}.wav"]
        );
        $audioAttached++;
    }
    $created++;
}

echo "Created {$created} rows on \"{$quiz->title}\", attached {$audioAttached} audio rows across ".count(THIRTEENTH_INSTRUMENTS)." instruments.".PHP_EOL;
