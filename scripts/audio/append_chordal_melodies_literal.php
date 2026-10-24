<?php

// Appends the 8 literal user-composed progressions as NEW rows onto the
// existing "Chordal melodies" pool, without touching the 117 rows already
// there (and without recreating anything, so other instruments' existing
// audio stays intact).

require __DIR__.'/chordal_melodies_pool.php';

use App\Models\Quiz;
use App\Models\QuizQuestion;

$quiz = Quiz::where('title', 'Chordal melodies')->firstOrFail();
$existingCount = $quiz->questions()->count();

$literal = chordalLiteralProgressions();
$manifest = [];
$position = $existingCount;
$created = 0;

foreach ($literal as $steps) {
    $voicings = chordalBuildVoicings($steps);
    $tops = array_map(fn ($v) => $v[count($v) - 1], $voicings);
    $label = implode(' ', array_map('chordalLabelForTop', $tops));
    $fileSlug = implode('-', array_map('chordalSlugForTop', $tops));

    $position++;
    $question = QuizQuestion::create([
        'quiz_id' => $quiz->id,
        'position' => $position,
        'prompt' => 'Tap the melody (top note of each chord) in the order you hear it.',
        'choices' => [$label],
        'answer' => $label,
    ]);

    $manifest[] = [
        'question_id' => $question->id,
        'file_slug' => $fileSlug.'-'.$position,
        'voicings' => $voicings,
    ];
    $created++;
}

file_put_contents(__DIR__.'/chordal_melodies_literal_piano_manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
echo "Appended {$created} new rows (pool now has ".$quiz->questions()->count()." total). Manifest written.".PHP_EOL;
