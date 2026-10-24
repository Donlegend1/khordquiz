<?php

// Attaches one instrument's audio to "Chordal melodies"'s existing 35-row
// pool (never recreates rows). Expects $sound, $folderSlug, and
// $transposeSemitones to already be set before this is required.
// $transposeSemitones shifts Piano's reference voicings up/down for an
// instrument with a different comfortable register (0 = same register as
// Piano).

require __DIR__.'/chordal_melodies_pool.php';

use App\Models\Quiz;
use App\Models\QuizQuestionAudio;

$quiz = Quiz::where('title', 'Chordal melodies')->firstOrFail();
$patterns = chordalMelodyPatterns();
$rows = $quiz->questions()->orderBy('position')->get(['id', 'position']);

$manifest = [];
$attached = 0;

foreach ($patterns as $i => $pattern) {
    $question = $rows[$i] ?? null;
    if (!$question) {
        echo "  missing row for pattern index {$i}.".PHP_EOL;
        continue;
    }

    $voicings = chordalTransposeVoicings($pattern['voicings'], $transposeSemitones);
    $fileSlug = $pattern['fileSlug'].'-'.($i + 1);
    $path = "melodic-dictation/chordal-melodies/{$folderSlug}/{$fileSlug}.wav";

    QuizQuestionAudio::updateOrCreate(
        ['quiz_question_id' => $question->id, 'sound' => $sound],
        ['audio_path' => $path]
    );

    $manifest[] = ['file_slug' => $fileSlug, 'voicings' => $voicings];
    $attached++;
}

file_put_contents(__DIR__."/chordal_melodies_{$folderSlug}_manifest.json", json_encode($manifest, JSON_PRETTY_PRINT));
echo "{$sound}: attached {$attached} rows, manifest written.".PHP_EOL;
