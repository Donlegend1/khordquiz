<?php

// Attaches the 84 rendered Piano files to "3-Note Stepwise"'s 84 pool rows, using
// the manifest written by build_three_note_stepwise_piano.php (question_id -> file_slug).

use App\Models\QuizQuestionAudio;

$manifest = json_decode(file_get_contents(__DIR__.'/three_note_stepwise_piano_manifest.json'), true);
$attached = 0;

foreach ($manifest as $row) {
    QuizQuestionAudio::updateOrCreate(
        ['quiz_question_id' => $row['question_id'], 'sound' => 'Piano'],
        ['audio_path' => "melodic-dictation/3-note-stepwise/piano/{$row['file_slug']}.wav"]
    );
    $attached++;
}

echo "Attached {$attached} rows.".PHP_EOL;
