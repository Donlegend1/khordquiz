<?php

// Collapses "Find the Note"'s 25 question rows down to 12 — one per distinct note
// (its real answer pool) — now that QuizController draws each attempt's 25 slots
// from that pool at play time instead of replaying 25 fixed stored rows. Keeps,
// per answer, whichever row already has Piano audio attached (there should be
// exactly one after attach_find_the_note_piano.php); deletes the rest.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;

$quiz = Quiz::where('title', 'Find the Note')->firstOrFail();

// Stale dev-only attempt with no recorded answers, predating question_sequence.
QuizAttempt::where('quiz_id', $quiz->id)->where('user_id', 2)->whereDoesntHave('answers')->delete();

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

echo "Kept {$kept} distinct notes, deleted {$deleted} duplicate rows. question_count stays 25 (the per-attempt draw size).".PHP_EOL;
