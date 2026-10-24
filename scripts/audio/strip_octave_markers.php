<?php

// Updates the stored `answer`/`choices` text for 3-Note Stepwise, 3-Note
// with Skips, 5-Note Melodies, and Dual-tonic melodies to drop octave
// markers (apostrophe/comma) — these quizzes test solfège recognition only,
// and the member's tap keyboard has no way to express an octave, so a
// correct answer requiring one was never actually answerable. Audio is
// untouched (it was always generated from raw MIDI, never from these
// labels); this only rewrites existing rows in place, in the same
// pattern x octave-slot order they were created in, matched by `position`.

use App\Models\Quiz;

function stripOctaveQuiz(string $title, array $patterns, array $octaveStarts): void
{
    $quiz = Quiz::where('title', $title)->firstOrFail();
    $rows = $quiz->questions()->orderBy('position')->get();

    $expectedCount = count($patterns) * count($octaveStarts);
    if ($rows->count() !== $expectedCount) {
        echo "{$title}: row count mismatch (db has {$rows->count()}, expected {$expectedCount}) — skipping.".PHP_EOL;
        return;
    }

    $updated = 0;
    $i = 0;
    foreach ($patterns as $pattern) {
        foreach ($octaveStarts as $octaveStart) {
            $row = $rows[$i];
            if ($row->answer !== $pattern['label']) {
                $row->update(['answer' => $pattern['label'], 'choices' => [$pattern['label']]]);
                $updated++;
            }
            $i++;
        }
    }

    echo "{$title}: updated {$updated} / {$rows->count()} rows.".PHP_EOL;
}

require_once __DIR__.'/three_note_stepwise_pool.php';
stripOctaveQuiz('3-Note Stepwise', stepwisePatterns(), [0, 1, 2]);

require_once __DIR__.'/three_note_skips_pool.php';
stripOctaveQuiz('3-Note with Skips', skipsPatterns(), [0, 1, 2]);

require_once __DIR__.'/five_note_melodies_pool.php';
stripOctaveQuiz('5-Note Melodies', fiveNoteMelodyPatterns(), [0, 1, 2]);

require_once __DIR__.'/dual_tonic_melodies_pool.php';
stripOctaveQuiz('Dual-tonic melodies', dualTonicMelodyPatterns(), [0, 1, 2]);
