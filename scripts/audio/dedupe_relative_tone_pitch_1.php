<?php

// Collapses "Relative Tone Pitch #1"'s 25 question rows down to 7 — one per
// distinct solfège degree (its real answer pool: Do Re Mi Fa So La Ti) — same
// pool-at-play-time model already applied to Find the Note. Keeps, per answer,
// whichever row already has audio attached (none yet here); deletes the rest.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;

$quiz = Quiz::where('title', 'Relative Tone Pitch #1')->firstOrFail();

// Stale in-progress attempt with no recorded answers, predating this dedupe —
// its question_sequence would otherwise reference rows we're about to delete.
QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();

$questions = $quiz->questions()->with('audioVariants')->get();
$kept = 0;
$deleted = 0;

foreach ($questions->groupBy('answer') as $answer => $group) {
    $keep = $group->first(fn (QuizQuestion $q) => $q->audioVariants->isNotEmpty()) ?? $group->first();
    $kept++;

    foreach ($group as $question) {
        if ($question->id !== $keep->id) {
            $question->delete();
            $deleted++;
        }
    }
}

$quiz->update(['question_count' => 25]);

echo "Kept {$kept} distinct degrees, deleted {$deleted} duplicate rows. question_count stays 25 (the per-attempt draw size).".PHP_EOL;
