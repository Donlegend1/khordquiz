<?php

// Builds "Dual-tonic melodies"'s real pool for Piano (C2-C5, 3 octaves) — 35
// curated patterns x 3 starting octaves = 105 rows. ONE-TIME pool creator:
// every other instrument attaches to these same rows via
// build_dual_tonic_melodies_instrument.php.

require __DIR__.'/dual_tonic_melodies_pool.php';

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;

$quiz = Quiz::where('title', 'Dual-tonic melodies')->firstOrFail();

if ($quiz->questions()->count() > 0) {
    echo 'Pool already exists ('.$quiz->questions()->count()." rows) — refusing to recreate it.".PHP_EOL;
    return;
}

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();

$base = 36; // C2
$octaveStarts = [0, 1, 2];
$patterns = dualTonicMelodyPatterns();

$manifest = [];
$created = 0;
$position = 0;

foreach ($patterns as $pattern) {
    foreach ($octaveStarts as $octaveStart) {
        $shiftedNotes = array_map(
            fn ($n) => ['type' => $n['type'], 'idx' => $n['idx'] + $octaveStart * 7],
            $pattern['notes']
        );
        $midiNotes = dualTonicMidiNotes($shiftedNotes, $base);

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

file_put_contents(__DIR__.'/dual_tonic_melodies_piano_manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
echo "Created {$created} pool rows (".count($patterns)." patterns x 3 octaves). Manifest written.".PHP_EOL;
