<?php

// Attaches one instrument's audio to "Dyad Melodies"'s existing 105-row pool
// (never recreates rows). Expects $sound, $folderSlug, $base, $octaveCount
// to already be set before this is required. Same shape as the other
// Melodic Dictation instrument scripts.

require __DIR__.'/dyad_melodies_pool.php';

use App\Models\Quiz;
use App\Models\QuizQuestionAudio;

$quiz = Quiz::where('title', 'Dyad Melodies')->firstOrFail();
$patterns = dyadMelodyPatterns();

$manifest = [];
$attached = 0;

foreach ($patterns as $pattern) {
    $rows = $quiz->questions()->where('answer', $pattern['label'])->orderBy('position')->get(['id']);

    for ($slot = 0; $slot < $octaveCount; $slot++) {
        $question = $rows[$slot] ?? null;
        if (!$question) {
            echo "  missing row for '{$pattern['label']}' slot {$slot}.".PHP_EOL;
            continue;
        }

        $shiftedDyads = array_map(
            fn ($d) => ['harmonyIdx' => $d['harmonyIdx'] + $slot * 7, 'topIdx' => $d['topIdx'] + $slot * 7],
            $pattern['dyads']
        );
        $voicings = dyadMelodyVoicings($shiftedDyads, $base);
        $fileSlug = "{$pattern['fileSlug']}-o{$slot}";
        $path = "melodic-dictation/dyad-melodies/{$folderSlug}/{$fileSlug}.wav";

        QuizQuestionAudio::updateOrCreate(
            ['quiz_question_id' => $question->id, 'sound' => $sound],
            ['audio_path' => $path]
        );

        $manifest[] = ['file_slug' => $fileSlug, 'voicings' => $voicings];
        $attached++;
    }
}

file_put_contents(__DIR__."/dyad_melodies_{$folderSlug}_manifest.json", json_encode($manifest, JSON_PRETTY_PRINT));
echo "{$sound}: attached {$attached} rows, manifest written.".PHP_EOL;
