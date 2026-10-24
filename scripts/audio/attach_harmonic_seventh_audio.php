<?php

// Attaches the rendered Do+Ti ... Ti+La' harmonic-seventh dyads to every
// "Harmonic Seventh" question, across the 6 sounds this quiz's picker offers.

use App\Models\Quiz;
use App\Models\QuizQuestionAudio;

$labelToSlug = [
    'Do - Ti' => 'do-ti', "Re - Do'" => 're-do2', "Mi - Re'" => 'mi-re2', "Fa - Mi'" => 'fa-mi2',
    "So - Fa'" => 'so-fa2', "La - So'" => 'la-so2', "Ti - La'" => 'ti-la2',
];

$sounds = ['Piano' => 'piano', 'Violin' => 'violin', 'Strings' => 'strings',
    'Guitar' => 'guitar', 'Marimba' => 'marimba', 'Voice' => 'voice'];

$quiz = Quiz::where('title', 'Harmonic Seventh')->firstOrFail();
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
            ['audio_path' => "intervals/harmonic-seventh/{$soundSlug}/{$pairSlug}.wav"]
        );
        $attached++;
    }
}

echo "Attached {$attached} audio rows across {$quiz->questions->count()} questions.".PHP_EOL;
