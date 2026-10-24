<?php

// Attaches the rendered Do-La ... Ti-So' melodic-sixth sequences to every
// "Melodic Sixth" question, across the 6 sounds this quiz's picker offers.

use App\Models\Quiz;
use App\Models\QuizQuestionAudio;

$labelToSlug = [
    'Do - La' => 'do-la', 'Re - Ti' => 're-ti', "Mi - Do'" => 'mi-do2', "Fa - Re'" => 'fa-re2',
    "So - Mi'" => 'so-mi2', "La - Fa'" => 'la-fa2', "Ti - So'" => 'ti-so2',
];

$sounds = ['Piano' => 'piano', 'Violin' => 'violin', 'Saxophone' => 'saxophone',
    'Marimba' => 'marimba', 'Guitar' => 'guitar', 'Voice' => 'voice'];

$quiz = Quiz::where('title', 'Melodic Sixth')->firstOrFail();
$attached = 0;

foreach ($quiz->questions as $question) {
    $pairSlug = $labelToSlug[$question->answer] ?? null;
    if (!$pairSlug) {
        echo "Skipping question {$question->id}: no pair mapping for answer '{$question->answer}'".PHP_EOL;
        continue;
    }

    foreach ($sounds as $soundLabel => $soundSlug) {
        QuizQuestionAudio::updateOrCreate(
            ['quiz_question_id' => $question->id, 'sound' => $soundLabel],
            ['audio_path' => "intervals/melodic-sixth/{$soundSlug}/{$pairSlug}.wav"]
        );
        $attached++;
    }
}

echo "Attached {$attached} audio rows across {$quiz->questions->count()} questions.".PHP_EOL;
