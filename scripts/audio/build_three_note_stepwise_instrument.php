<?php

// Attaches one instrument's audio to "3-Note Stepwise"'s existing 84-row pool
// (never recreates rows — see three_note_stepwise_pool.php). Expects $sound,
// $folderSlug, $base (MIDI of this instrument's slot-0 "Do"), and $octaveCount
// (how many of the 3 slots fit this instrument's own range — 2 or 3) to already
// be set before this is required. Writes a render manifest (question audio isn't
// rendered here, just the DB attach + a manifest for the bash render step) and
// creates the QuizQuestionAudio rows at the paths that manifest will render to.

require __DIR__.'/three_note_stepwise_pool.php';

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

$quiz = Quiz::where('title', '3-Note Stepwise')->firstOrFail();
$patterns = stepwisePatterns();

$manifest = [];
$attached = 0;

foreach ($patterns as $pattern) {
    $rows = $quiz->questions()->where('answer', $pattern['label'])->orderBy('position')->get(['id']);

    for ($slot = 0; $slot < $octaveCount; $slot++) {
        $question = $rows[$slot] ?? null;
        if (!$question) {
            echo "  missing row for '{$pattern['label']}' slot {$slot} — pool may need more than 3 slots.".PHP_EOL;
            continue;
        }

        $absoluteStart = $slot * 7 + $pattern['startDegree'];
        $midiNotes = array_map(fn ($r) => stepwiseMidiForIndex($absoluteStart + $r, $base), $pattern['relativeIndices']);
        $fileSlug = "{$pattern['fileSlug']}-o{$slot}";
        $path = "melodic-dictation/3-note-stepwise/{$folderSlug}/{$fileSlug}.wav";

        QuizQuestionAudio::updateOrCreate(
            ['quiz_question_id' => $question->id, 'sound' => $sound],
            ['audio_path' => $path]
        );

        $manifest[] = ['file_slug' => $fileSlug, 'notes' => $midiNotes];
        $attached++;
    }
}

file_put_contents(__DIR__."/three_note_stepwise_{$folderSlug}_manifest.json", json_encode($manifest, JSON_PRETTY_PRINT));
echo "{$sound}: attached {$attached} rows, manifest written.".PHP_EOL;
