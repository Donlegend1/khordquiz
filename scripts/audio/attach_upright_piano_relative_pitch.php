<?php

// Attaches "Upright Piano" audio to every Relative Pitch / Intervals question
// that already has Piano audio — mirrors each Piano row's path, swapping the
// "piano" folder segment for "upright-piano" (same filenames either way,
// confirmed identical between the two instrument's note libraries).

use App\Models\QuizQuestionAudio;

$pianoRows = QuizQuestionAudio::where('sound', 'Piano')
    ->where(function ($q) {
        $q->where('audio_path', 'like', 'notes/piano/%')
            ->orWhere('audio_path', 'like', 'intervals/%/piano/%');
    })
    ->get();

$attached = 0;
foreach ($pianoRows as $row) {
    $uprightPath = preg_replace('#/piano/#', '/upright-piano/', $row->audio_path);

    QuizQuestionAudio::updateOrCreate(
        ['quiz_question_id' => $row->quiz_question_id, 'sound' => 'Upright Piano'],
        ['audio_path' => $uprightPath]
    );
    $attached++;
}

echo "Attached Upright Piano audio to {$attached} questions.".PHP_EOL;
