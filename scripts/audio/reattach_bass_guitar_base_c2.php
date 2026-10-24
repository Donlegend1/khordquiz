<?php

// Moves Bass Guitar's base octave (Find the Note, RTP1) down from C3-C4 to C2-C3,
// per explicit instruction — a real bass guitar's open low string (E1) sits much
// closer to C2 than C3. Points at the newly-rendered "-c2" files rather than
// overwriting the bare notes/bass-guitar/{slug}.wav files, which RTP2 still
// reuses as its own C3-octave entries and must not be disturbed.
// The 12 interval quizzes were regenerated in place at the same paths (their
// folders aren't shared with RTP2), so their existing QuizQuestionAudio rows
// already serve the new C2-rooted audio with no DB change needed.

use App\Models\Quiz;
use App\Models\QuizQuestionAudio;

$noteLabelToSlug = [
    'C' => 'c', 'C#/Db' => 'csharp', 'D' => 'd', 'D#/Eb' => 'dsharp', 'E' => 'e', 'F' => 'f',
    'F#/Gb' => 'fsharp', 'G' => 'g', 'G#/Ab' => 'gsharp', 'A' => 'a', 'A#/Bb' => 'asharp', 'B' => 'b',
];
$degreeLabelToSlug = ['Do' => 'do', 'Re' => 're', 'Mi' => 'mi', 'Fa' => 'fa', 'So' => 'so', 'La' => 'la', 'Ti' => 'ti'];

function reattach(string $quizTitle, array $labelToSlug): void
{
    $quiz = Quiz::where('title', $quizTitle)->firstOrFail();
    $updated = 0;

    foreach ($quiz->questions as $question) {
        $slug = $labelToSlug[$question->answer] ?? null;
        if (!$slug) continue;

        QuizQuestionAudio::updateOrCreate(
            ['quiz_question_id' => $question->id, 'sound' => 'Bass Guitar'],
            ['audio_path' => "notes/bass-guitar/{$slug}-c2.wav"]
        );
        $updated++;
    }

    echo "{$quizTitle}: repointed {$updated} of {$quiz->questions->count()} questions to C2.".PHP_EOL;
}

reattach('Find the Note', $noteLabelToSlug);
reattach('Relative Tone Pitch #1', $degreeLabelToSlug);
