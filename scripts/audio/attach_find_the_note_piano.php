<?php

// Attaches the rendered Piano note files to every "Find the Note" question, matching
// each question's `answer` to the note file with that name. One-off script, run once
// via `php artisan tinker --execute="require ...`"` or `php artisan db:seed`-style —
// kept here (not a migration) since it's a content-population step, not a schema change.

use App\Models\Quiz;
use App\Models\QuizQuestionAudio;

$labelToSlug = [
    'C' => 'c', 'C#/Db' => 'csharp', 'D' => 'd', 'D#/Eb' => 'dsharp', 'E' => 'e', 'F' => 'f',
    'F#/Gb' => 'fsharp', 'G' => 'g', 'G#/Ab' => 'gsharp', 'A' => 'a', 'A#/Bb' => 'asharp', 'B' => 'b',
];

$quiz = Quiz::where('title', 'Find the Note')->firstOrFail();
$attached = 0;

foreach ($quiz->questions as $question) {
    $slug = $labelToSlug[$question->answer] ?? null;
    if (!$slug) {
        echo "Skipping question {$question->id}: no note mapping for answer '{$question->answer}'".PHP_EOL;
        continue;
    }

    QuizQuestionAudio::updateOrCreate(
        ['quiz_question_id' => $question->id, 'sound' => 'Piano'],
        ['audio_path' => "notes/piano/{$slug}.wav"]
    );
    $attached++;
}

echo "Attached Piano audio to {$attached} of {$quiz->questions->count()} questions.".PHP_EOL;
