<?php

use App\Models\QuizQuestionAudio;

$manifest = json_decode(file_get_contents(__DIR__.'/dual_tonic_melodies_piano_manifest.json'), true);
$attached = 0;

foreach ($manifest as $row) {
    QuizQuestionAudio::updateOrCreate(
        ['quiz_question_id' => $row['question_id'], 'sound' => 'Piano'],
        ['audio_path' => "melodic-dictation/dual-tonic-melodies/piano/{$row['file_slug']}.wav"]
    );
    $attached++;
}

echo "Attached {$attached} rows.".PHP_EOL;
