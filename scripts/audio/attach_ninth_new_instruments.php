<?php

// Attaches Trombone, Saxophone, Marimba, Celesta, and Vibraphone audio to
// 9th Degree Chords Levels 1-5's EXISTING question rows (does not rebuild
// questions). For each question, reads Piano's existing audio_path and
// reuses its filename for each new instrument's own folder.

use App\Models\Quiz;
use App\Models\QuizQuestionAudio;

const NEW_INSTRUMENTS = [
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

const LEVEL_PATH_PREFIXES = [
    '9th Degree Chords · Level 1' => 'ninth-degree-chords/level1',
    '9th Degree Chords · Level 2' => 'ninth-degree-chords/level2',
    '9th Degree Chords · Level 3' => 'ninth-degree-chords/level3',
    '9th Degree Chords · Level 4' => 'ninth-degree-chords/level4',
    '9th Degree Chords · Level 5' => 'ninth-degree-chords/level5',
];

foreach (LEVEL_PATH_PREFIXES as $title => $pathPrefix) {
    $quiz = Quiz::where('title', $title)->firstOrFail();
    $attached = 0;

    foreach ($quiz->questions as $question) {
        $pianoAudio = QuizQuestionAudio::where('quiz_question_id', $question->id)->where('sound', 'Piano')->first();
        if (!$pianoAudio) continue;
        $suffix = basename($pianoAudio->audio_path, '.wav');

        foreach (NEW_INSTRUMENTS as $sound => $folderSlug) {
            QuizQuestionAudio::updateOrCreate(
                ['quiz_question_id' => $question->id, 'sound' => $sound],
                ['audio_path' => "{$pathPrefix}/{$folderSlug}/{$suffix}.wav"]
            );
            $attached++;
        }
    }

    echo "Attached {$attached} rows on \"{$title}\".".PHP_EOL;
}
