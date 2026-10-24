<?php

// Builds "3-Note with Skips"'s real pool for Piano (C2-C5, 3 octaves) — 224
// patterns x 3 starting octaves = 672 rows. ONE-TIME pool creator: every other
// instrument (build_three_note_skips_instrument.php) attaches to these same
// rows — never recreate them, or every other instrument's audio gets
// cascade-deleted along with the rows.

require __DIR__.'/three_note_skips_pool.php';

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;

$quiz = Quiz::where('title', '3-Note with Skips')->firstOrFail();

if ($quiz->questions()->count() > 0) {
    echo 'Pool already exists ('.$quiz->questions()->count()." rows) — refusing to recreate it. Use build_three_note_skips_instrument.php for other instruments instead.".PHP_EOL;
    return;
}

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();

$base = 36; // C2
$octaveStarts = [0, 1, 2]; // C2, C3, C4 — the three starting octaves within C2-C5
$patterns = skipsPatterns();

$manifest = [];
$created = 0;
$position = 0;

foreach ($patterns as $pattern) {
    foreach ($octaveStarts as $octaveStart) {
        $absoluteStart = $octaveStart * 7 + $pattern['startDegree'];
        $midiNotes = array_map(fn ($r) => stepwiseMidiForIndex($absoluteStart + $r, $base), $pattern['relativeIndices']);

        $position++;
        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'position' => $position,
            'prompt' => 'Tap the notes in the order you hear them.',
            'choices' => [$pattern['label']],
            'answer' => $pattern['label'],
        ]);

        $manifest[] = [
            'question_id' => $question->id,
            'file_slug' => "{$pattern['fileSlug']}-o{$octaveStart}",
            'notes' => $midiNotes,
        ];
        $created++;
    }
}

file_put_contents(__DIR__.'/three_note_skips_piano_manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
echo "Created {$created} pool rows (".count($patterns)." patterns x 3 octaves). Manifest written.".PHP_EOL;
