<?php

// Collapses "Melodic Sixth"'s 25 question rows down to 7 — one per distinct
// sixth-apart degree pair, same pool-at-play-time model as the other solfège quizzes.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;

$quiz = Quiz::where('title', 'Melodic Sixth')->firstOrFail();

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

echo "Kept {$kept} distinct pairs, deleted {$deleted} duplicate rows.".PHP_EOL;
