<?php

// Builds "Chordal melodies"'s real pool for Piano — 35 curated progressions,
// ONE row per progression (no octave-slot multiplication like the other
// Melodic Dictation quizzes, since each progression's voicing is already a
// deliberately chosen register). ONE-TIME pool creator: every other
// instrument attaches to these same rows (matched by `position`) via
// build_chordal_melodies_instrument.php.

require __DIR__.'/chordal_melodies_pool.php';

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;

$quiz = Quiz::where('title', 'Chordal melodies')->firstOrFail();

if ($quiz->questions()->count() > 0) {
    echo 'Pool already exists ('.$quiz->questions()->count()." rows) — refusing to recreate it.".PHP_EOL;
    return;
}

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();

$patterns = chordalMelodyPatterns();

$manifest = [];
$created = 0;
$position = 0;

foreach ($patterns as $pattern) {
    $position++;
    $question = QuizQuestion::create([
        'quiz_id' => $quiz->id,
        'position' => $position,
        'prompt' => 'Tap the melody (top note of each chord) in the order you hear it.',
        'choices' => [$pattern['label']],
        'answer' => $pattern['label'],
    ]);

    $manifest[] = [
        'question_id' => $question->id,
        'position' => $position,
        'file_slug' => $pattern['fileSlug'].'-'.$position,
        'voicings' => $pattern['voicings'],
    ];
    $created++;
}

file_put_contents(__DIR__.'/chordal_melodies_piano_manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
echo "Created {$created} pool rows (".count($patterns)." progressions). Manifest written.".PHP_EOL;
