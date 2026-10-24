<?php

// Attaches the newly-rendered Saxophone dyads to the six Harmonic interval quizzes
// (Second-Seventh) — Brass was deliberately excluded from these when first built
// (a real sax can't play two notes at once), but it's since been decided to include
// it anyway, same as every other instrument here plays an unrealistic-but-useful
// dyad via the synth.

use App\Models\Quiz;
use App\Models\QuizQuestionAudio;

function attach(string $quizTitle, array $labelToSlug, string $pathPrefix): void
{
    $quiz = Quiz::where('title', $quizTitle)->firstOrFail();
    $attached = 0;

    foreach ($quiz->questions as $question) {
        $slug = $labelToSlug[$question->answer] ?? null;
        if (!$slug) continue;

        QuizQuestionAudio::updateOrCreate(
            ['quiz_question_id' => $question->id, 'sound' => 'Saxophone'],
            ['audio_path' => "{$pathPrefix}/{$slug}.wav"]
        );
        $attached++;
    }

    echo "{$quizTitle}: attached {$attached} of {$quiz->questions->count()} questions.".PHP_EOL;
}

$intervalPairs = [
    'second' => ['Do - Re' => 'do-re', 'Re - Mi' => 're-mi', 'Mi - Fa' => 'mi-fa', 'Fa - So' => 'fa-so', 'So - La' => 'so-la', 'La - Ti' => 'la-ti', 'Ti - Do' => 'ti-do'],
    'third' => ['Do - Mi' => 'do-mi', 'Re - Fa' => 're-fa', 'Mi - So' => 'mi-so', 'Fa - La' => 'fa-la', 'So - Ti' => 'so-ti', 'La - Do' => 'la-do', 'Ti - Re' => 'ti-re'],
    'fourth' => ['Do - Fa' => 'do-fa', 'Re - So' => 're-so', 'Mi - La' => 'mi-la', 'Fa - Ti' => 'fa-ti', "So - Do'" => 'so-do2', "La - Re'" => 'la-re2', "Ti - Mi'" => 'ti-mi2'],
    'fifth' => ['Do - So' => 'do-so', 'Re - La' => 're-la', 'Mi - Ti' => 'mi-ti', "Fa - Do'" => 'fa-do2', "So - Re'" => 'so-re2', "La - Mi'" => 'la-mi2', "Ti - Fa'" => 'ti-fa2'],
    'sixth' => ['Do - La' => 'do-la', 'Re - Ti' => 're-ti', "Mi - Do'" => 'mi-do2', "Fa - Re'" => 'fa-re2', "So - Mi'" => 'so-mi2', "La - Fa'" => 'la-fa2', "Ti - So'" => 'ti-so2'],
    'seventh' => ['Do - Ti' => 'do-ti', "Re - Do'" => 're-do2', "Mi - Re'" => 'mi-re2', "Fa - Mi'" => 'fa-mi2', "So - Fa'" => 'so-fa2', "La - So'" => 'la-so2', "Ti - La'" => 'ti-la2'],
];
$intervalTitles = ['second' => 'Second', 'third' => 'Third', 'fourth' => 'Fourth', 'fifth' => 'Fifth', 'sixth' => 'Sixth', 'seventh' => 'Seventh'];

foreach ($intervalPairs as $key => $labelToSlug) {
    $title = "Harmonic {$intervalTitles[$key]}";
    attach($title, $labelToSlug, "intervals/harmonic-{$key}/saxophone");
}
