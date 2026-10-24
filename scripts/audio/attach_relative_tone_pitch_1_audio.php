<?php

// Attaches the rendered Do-Re-Mi-Fa-So-La-Ti note files (C3-B3, one octave) to every
// "Relative Tone Pitch #1" question, across all 8 sounds.

use App\Models\Quiz;
use App\Models\QuizQuestionAudio;

$labelToSlug = [
    'Do' => 'do', 'Re' => 're', 'Mi' => 'mi', 'Fa' => 'fa', 'So' => 'so', 'La' => 'la', 'Ti' => 'ti',
];

$sounds = ['Piano' => 'piano', 'EP' => 'ep', 'Violin' => 'violin', 'Saxophone' => 'saxophone',
    'Marimba' => 'marimba', 'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Voice' => 'voice'];

$quiz = Quiz::where('title', 'Relative Tone Pitch #1')->firstOrFail();
$attached = 0;

foreach ($quiz->questions as $question) {
    $noteSlug = $labelToSlug[$question->answer] ?? null;
    if (!$noteSlug) {
        echo "Skipping question {$question->id}: no degree mapping for answer '{$question->answer}'".PHP_EOL;
        continue;
    }

    foreach ($sounds as $soundLabel => $soundSlug) {
        QuizQuestionAudio::updateOrCreate(
            ['quiz_question_id' => $question->id, 'sound' => $soundLabel],
            ['audio_path' => "notes/{$soundSlug}/{$noteSlug}.wav"]
        );
        $attached++;
    }
}

echo "Attached {$attached} audio rows across {$quiz->questions->count()} questions.".PHP_EOL;
