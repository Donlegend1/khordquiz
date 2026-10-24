<?php

// Attaches the rendered Do-Re ... Ti-Do melodic-second sequences (C3-C4) to every
// "Melodic Second" question, across all 8 sounds.

use App\Models\Quiz;
use App\Models\QuizQuestionAudio;

$labelToSlug = [
    'Do - Re' => 'do-re', 'Re - Mi' => 're-mi', 'Mi - Fa' => 'mi-fa', 'Fa - So' => 'fa-so',
    'So - La' => 'so-la', 'La - Ti' => 'la-ti', 'Ti - Do' => 'ti-do',
];

$sounds = ['Piano' => 'piano', 'EP' => 'ep', 'Violin' => 'violin', 'Saxophone' => 'saxophone',
    'Marimba' => 'marimba', 'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Voice' => 'voice'];

$quiz = Quiz::where('title', 'Melodic Second')->firstOrFail();
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
            ['audio_path' => "intervals/melodic-second/{$soundSlug}/{$pairSlug}.wav"]
        );
        $attached++;
    }
}

echo "Attached {$attached} audio rows across {$quiz->questions->count()} questions.".PHP_EOL;
