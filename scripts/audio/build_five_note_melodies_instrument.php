<?php

// Attaches one instrument's audio to "5-Note Melodies"'s existing 105-row pool
// (never recreates rows). Expects $sound, $folderSlug, $base, $octaveCount to
// already be set before this is required. Same shape as the Stepwise/Skips
// instrument scripts.

require __DIR__.'/five_note_melodies_pool.php';

use App\Models\Quiz;
use App\Models\QuizQuestionAudio;

$quiz = Quiz::where('title', '5-Note Melodies')->firstOrFail();
$patterns = fiveNoteMelodyPatterns();

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

        $absoluteStart = $slot * 7 + $pattern['startDegree'];
        $midiNotes = array_map(fn ($r) => stepwiseMidiForIndex($absoluteStart + $r, $base), $pattern['relativeIndices']);
        $fileSlug = "{$pattern['fileSlug']}-o{$slot}";
        $path = "melodic-dictation/5-note-melodies/{$folderSlug}/{$fileSlug}.wav";

        QuizQuestionAudio::updateOrCreate(
            ['quiz_question_id' => $question->id, 'sound' => $sound],
            ['audio_path' => $path]
        );

        $manifest[] = ['file_slug' => $fileSlug, 'notes' => $midiNotes];
        $attached++;
    }
}

file_put_contents(__DIR__."/five_note_melodies_{$folderSlug}_manifest.json", json_encode($manifest, JSON_PRETTY_PRINT));
echo "{$sound}: attached {$attached} rows, manifest written.".PHP_EOL;
