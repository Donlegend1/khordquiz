<?php

// Builds "Chord naming" — replaces the 5-row quality-only placeholder with
// real content: 5 qualities x 10 curated degree/exclude combos = 50 rows —
// the admin shows exactly these 50 rows (not padded); QuizController::
// buildQuestionSequence() fair-shuffles a random 25 per attempt from the
// real pool. `answer` is the pipe-delimited encoding
// resources/js/lib/quizAnswerPresets.js's encodeNamingAnswer() produces, so
// QuizController::answer()'s $data['choice'] === $question->answer check
// works against what the frontend actually submits. Rebuilds the quiz's
// questions and every instrument's attached audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const CHORD_NAMING_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', 'Chord naming')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$rows = [];
exec('php '.__DIR__.'/gen_chord_naming_voicings.php 48', $rows);

$position = 0;
$created = 0;
$audioAttached = 0;

foreach ($rows as $line) {
    [$slug, $answer] = explode("\t", $line);
    $position++;

    $question = QuizQuestion::create([
        'quiz_id' => $quiz->id,
        'position' => $position,
        'prompt' => 'Listen to the chord and build its full name.',
        'choices' => [],
        'answer' => $answer,
    ]);

    foreach (CHORD_NAMING_INSTRUMENTS as $sound => $folderSlug) {
        QuizQuestionAudio::updateOrCreate(
            ['quiz_question_id' => $question->id, 'sound' => $sound],
            ['audio_path' => "extensions/chord-naming/{$folderSlug}/{$slug}.wav"]
        );
        $audioAttached++;
    }
    $created++;
}

echo "Created {$created} rows on \"{$quiz->title}\" (5 qualities x 10 combos; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(CHORD_NAMING_INSTRUMENTS)." instruments.".PHP_EOL;
