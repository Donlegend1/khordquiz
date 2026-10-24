<?php

// Attaches the rendered Do+Re ... Ti+Do harmonic-second dyads (C3-C4, both notes
// together) to every "Harmonic Second" question. Only the 6 sounds this quiz's
// picker actually offers (QuizCatalog::soundsFor) — no Saxophone (can't play two
// notes at once), no EP/Jazz Guitar (not in this quiz's sound list).

use App\Models\Quiz;
use App\Models\QuizQuestionAudio;

$labelToSlug = [
    'Do - Re' => 'do-re', 'Re - Mi' => 're-mi', 'Mi - Fa' => 'mi-fa', 'Fa - So' => 'fa-so',
    'So - La' => 'so-la', 'La - Ti' => 'la-ti', 'Ti - Do' => 'ti-do',
];

$sounds = ['Piano' => 'piano', 'Violin' => 'violin', 'Strings' => 'strings',
    'Guitar' => 'guitar', 'Marimba' => 'marimba', 'Voice' => 'voice'];

$quiz = Quiz::where('title', 'Harmonic Second')->firstOrFail();
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
            ['audio_path' => "intervals/harmonic-second/{$soundSlug}/{$pairSlug}.wav"]
        );
        $attached++;
    }
}

echo "Attached {$attached} audio rows across {$quiz->questions->count()} questions.".PHP_EOL;
