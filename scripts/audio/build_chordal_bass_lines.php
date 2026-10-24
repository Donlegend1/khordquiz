<?php

// Builds "Chordal Bass Lines" from ALL of Chordal melodies' current
// progressions — every chord gets converted to drop-2 voicing (2nd-from-top
// note dropped an octave), which opens the voicing and usually lowers the
// bass note. The melody (top note) is identical to Chordal melodies; the
// answer here is each chord's new BOTTOM note instead. Audio is rendered
// fresh (drop-2 voicings are genuinely different chords from Chordal
// melodies' close-position ones, so nothing is reused). Rebuilds the whole
// quiz from scratch every run — re-run any time Chordal melodies changes.

require __DIR__.'/chordal_melodies_pool.php';

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;

$quiz = Quiz::where('title', 'Chordal Bass Lines')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$patterns = chordalLiteralProgressions();

$manifest = [];
$position = 0;
$created = 0;

foreach ($patterns as $steps) {
    $voicings = chordalBuildVoicings($steps);
    $dropVoicings = chordalDrop2Voicings($voicings);
    $bassNotes = array_map(fn ($v) => $v[0], $dropVoicings);
    $label = chordalStripOctaves(implode(' ', array_map('chordalLabelForTop', $bassNotes)));
    $fileSlug = implode('-', array_map('chordalSlugForTop', $bassNotes));

    $position++;
    $question = QuizQuestion::create([
        'quiz_id' => $quiz->id,
        'position' => $position,
        'prompt' => 'Tap the bass line (bottom note of each chord) in the order you hear it.',
        'choices' => [$label],
        'answer' => $label,
    ]);

    $manifest[] = [
        'question_id' => $question->id,
        'file_slug' => $fileSlug.'-'.$position,
        'voicings' => $dropVoicings,
    ];
    $created++;
}

file_put_contents(__DIR__.'/chordal_bass_lines_piano_manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
echo "Created {$created} rows (drop-2 of all ".count($patterns)." Chordal melodies patterns). Manifest written.".PHP_EOL;
