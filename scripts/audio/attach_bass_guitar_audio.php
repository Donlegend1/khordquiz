<?php

// Attaches the newly-rendered Bass Guitar audio (notes/bass-guitar/* and
// intervals/*/bass-guitar/*) to every "Relative Pitch" quiz built so far. Same
// nine-quiz shape as attach_organ_audio.php, except RTP2 only gets octaves 2-4
// (agreed range — a real bass guitar's open strings bottom out around E1, so C1
// sounds muddy, and C5+ is past its realistic register).
//
// Find the Note and RTP1's base octave was later moved down to C2-C3 (same E1
// reasoning, applied to the single-octave quizzes too) — they point at the
// "-c2" files, not the bare notes/bass-guitar/{slug}.wav ones, which RTP2 still
// reuses as its own C3-octave entries and must stay untouched.

use App\Models\Quiz;
use App\Models\QuizQuestionAudio;

function attach(string $quizTitle, array $labelToSlug, string $pathPrefix, string $suffix = ''): void
{
    $quiz = Quiz::where('title', $quizTitle)->firstOrFail();
    $attached = 0;

    foreach ($quiz->questions as $question) {
        $slug = $labelToSlug[$question->answer] ?? null;
        if (!$slug) {
            echo "  skipping question {$question->id}: no mapping for answer '{$question->answer}'".PHP_EOL;
            continue;
        }

        QuizQuestionAudio::updateOrCreate(
            ['quiz_question_id' => $question->id, 'sound' => 'Bass Guitar'],
            ['audio_path' => "{$pathPrefix}/{$slug}{$suffix}.wav"]
        );
        $attached++;
    }

    echo "{$quizTitle}: attached {$attached} of {$quiz->questions->count()} questions.".PHP_EOL;
}

$noteLabelToSlug = [
    'C' => 'c', 'C#/Db' => 'csharp', 'D' => 'd', 'D#/Eb' => 'dsharp', 'E' => 'e', 'F' => 'f',
    'F#/Gb' => 'fsharp', 'G' => 'g', 'G#/Ab' => 'gsharp', 'A' => 'a', 'A#/Bb' => 'asharp', 'B' => 'b',
];
attach('Find the Note', $noteLabelToSlug, 'notes/bass-guitar', '-c2');

$degreeLabelToSlug = ['Do' => 'do', 'Re' => 're', 'Mi' => 'mi', 'Fa' => 'fa', 'So' => 'so', 'La' => 'la', 'Ti' => 'ti'];
attach('Relative Tone Pitch #1', $degreeLabelToSlug, 'notes/bass-guitar', '-c2');

$rtp2AllowedOctaves = [2, 3, 4];
$ids = json_decode(file_get_contents(__DIR__.'/relative_tone_pitch_2_question_ids.json'), true);
$rtp2Attached = 0;
foreach ($ids as $octave => $byDegree) {
    if (!in_array((int) $octave, $rtp2AllowedOctaves, true)) continue;

    foreach ($byDegree as $degree => $questionId) {
        $slug = $degreeLabelToSlug[$degree] ?? null;
        if (!$slug) continue;
        $filename = $octave == 3 ? "{$slug}.wav" : "{$slug}-c{$octave}.wav";
        QuizQuestionAudio::updateOrCreate(
            ['quiz_question_id' => $questionId, 'sound' => 'Bass Guitar'],
            ['audio_path' => "notes/bass-guitar/{$filename}"]
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
        $title = "{$kind} {$intervalTitles[$key]}";
        $folder = strtolower($kind) . "-{$key}";
        attach($title, $labelToSlug, "intervals/{$folder}/bass-guitar");
    }
}
