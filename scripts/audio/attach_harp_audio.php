<?php

// Attaches the newly-rendered Harp audio to all 15 Relative Pitch quizzes — full
// C1-C6 for RTP2, no range cap, since a concert harp genuinely spans roughly
// C1-G7 in real life.

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
            ['quiz_question_id' => $question->id, 'sound' => 'Harp'],
            ['audio_path' => "{$pathPrefix}/{$slug}.wav"]
        );
        $attached++;
    }

    echo "{$quizTitle}: attached {$attached} of {$quiz->questions->count()} questions.".PHP_EOL;
}

$noteLabelToSlug = [
    'C' => 'c', 'C#/Db' => 'csharp', 'D' => 'd', 'D#/Eb' => 'dsharp', 'E' => 'e', 'F' => 'f',
    'F#/Gb' => 'fsharp', 'G' => 'g', 'G#/Ab' => 'gsharp', 'A' => 'a', 'A#/Bb' => 'asharp', 'B' => 'b',
];
attach('Find the Note', $noteLabelToSlug, 'notes/harp');

$degreeLabelToSlug = ['Do' => 'do', 'Re' => 're', 'Mi' => 'mi', 'Fa' => 'fa', 'So' => 'so', 'La' => 'la', 'Ti' => 'ti'];
attach('Relative Tone Pitch #1', $degreeLabelToSlug, 'notes/harp');

$ids = json_decode(file_get_contents(__DIR__.'/relative_tone_pitch_2_question_ids.json'), true);
$rtp2Attached = 0;
foreach ($ids as $octave => $byDegree) {
    if ((int) $octave === 7) continue; // Harp doesn't go that high here — only Marimba does.
    foreach ($byDegree as $degree => $questionId) {
        $slug = $degreeLabelToSlug[$degree] ?? null;
        if (!$slug) continue;
        $filename = $octave == 3 ? "{$slug}.wav" : "{$slug}-c{$octave}.wav";
        QuizQuestionAudio::updateOrCreate(
            ['quiz_question_id' => $questionId, 'sound' => 'Harp'],
            ['audio_path' => "notes/harp/{$filename}"]
        );
        $rtp2Attached++;
    }
}
echo "Relative Tone Pitch #2: attached {$rtp2Attached} rows.".PHP_EOL;

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
    foreach (['Melodic', 'Harmonic'] as $kind) {
        attach("{$kind} {$intervalTitles[$key]}", $labelToSlug, 'intervals/' . strtolower($kind) . "-{$key}/harp");
    }
}
