<?php

// Attaches Trombone, Saxophone, Marimba, Celesta, and Vibraphone audio to
// 7th Degree Chords Levels 1-8's EXISTING question rows (does not rebuild
// questions). For each question, reads Piano's existing audio_path and
// reuses its filename for each new instrument's own folder.

use App\Models\Quiz;
use App\Models\QuizQuestionAudio;

const NEW_INSTRUMENTS = [
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

const LEVEL_PATH_PREFIXES = [
    '7th Degree Chords · Level 1' => 'seventh-degree-chords/level1',
    '7th Degree Chords · Level 2' => 'seventh-degree-chords/level2',
    '7th Degree Chords · Level 3' => 'seventh-degree-chords/level3',
    '7th Degree Chords · Level 4' => 'seventh-degree-chords/level4',
    '7th Degree Chords · Level 5' => 'seventh-degree-chords/level5',
    '7th Degree Chords · Level 6' => 'seventh-degree-chords/level6',
    '7th Degree Chords · Level 7' => 'seventh-degree-chords/level7',
    '7th Degree Chords · Level 8' => 'seventh-degree-chords/level8',
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
