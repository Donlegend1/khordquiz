<?php

// Attaches the rendered Do-Fa ... Ti-Mi' melodic-fourth sequences to every
// "Melodic Fourth" question, across the 6 sounds this quiz's picker offers.

use App\Models\Quiz;
use App\Models\QuizQuestionAudio;

$labelToSlug = [
    'Do - Fa' => 'do-fa', 'Re - So' => 're-so', 'Mi - La' => 'mi-la', 'Fa - Ti' => 'fa-ti',
    "So - Do'" => 'so-do2', "La - Re'" => 'la-re2', "Ti - Mi'" => 'ti-mi2',
];

$sounds = ['Piano' => 'piano', 'Violin' => 'violin', 'Saxophone' => 'saxophone',
    'Marimba' => 'marimba', 'Guitar' => 'guitar', 'Voice' => 'voice'];

$quiz = Quiz::where('title', 'Melodic Fourth')->firstOrFail();
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
            ['audio_path' => "intervals/melodic-fourth/{$soundSlug}/{$pairSlug}.wav"]
        );
        $attached++;
    }
}

echo "Attached {$attached} audio rows across {$quiz->questions->count()} questions.".PHP_EOL;
