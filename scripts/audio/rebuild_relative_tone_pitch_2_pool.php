<?php

// Relative Tone Pitch #2's pool spans octaves C2-C6 (unlike #1's single C3-C4
// octave) — 7 solfège degrees x 5 octaves = 35 distinct rows, since each
// (degree, octave) pair needs its own audio per sound. C1 was dropped from
// the range (was previously C1-C6/42 rows) per user decision.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;

$quiz = Quiz::where('title', 'Relative Tone Pitch #2')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();

$degrees = ['Do', 'Re', 'Mi', 'Fa', 'So', 'La', 'Ti'];
$octaves = [2, 3, 4, 5, 6];
// choicesFor() overrides this with the preset list whenever the answer matches
// it (it does, always, here) — populated anyway since the column is non-null.
$choices = $degrees;

$quiz->questions()->delete();

// No schema column for octave — quiz_questions only needs `answer` (the
// solfège degree, octave-invariant) to drive choices/scoring; which octave
// each row represents lives entirely in the audio file path attached to it
// (see attach_relative_tone_pitch_2_audio.php), in row-creation order below.
$created = 0;
$ids = [];
foreach ($octaves as $octave) {
    foreach ($degrees as $degree) {
        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            // No longer meaningful (the attempt-time shuffle draws from the whole
            // pool, not stored position), just a required not-null column.
            'position' => $created + 1,
            'prompt' => 'Listen to the note and identify which solfège syllable it is.',
            'choices' => $choices,
            'answer' => $degree,
        ]);
        $ids[$octave][$degree] = $question->id;
        $created++;
    }
}

file_put_contents(__DIR__.'/relative_tone_pitch_2_question_ids.json', json_encode($ids, JSON_PRETTY_PRINT));
echo "Rebuilt pool with {$created} rows (7 degrees x 5 octaves, C2-C6). IDs written to relative_tone_pitch_2_question_ids.json.".PHP_EOL;
