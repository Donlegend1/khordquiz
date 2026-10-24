<?php

// Attaches Trombone, Saxophone, Marimba, Celesta, and Vibraphone audio to
// Basic Triads Levels 1-8's EXISTING question rows (does not rebuild
// questions). For each question, reads Piano's existing audio_path (which
// already encodes the exact key/quality/inversion slug for that row) and
// reuses its filename for each new instrument's own folder — avoids
// reconstructing any level's cycling/pool logic from scratch.

use App\Models\Quiz;
use App\Models\QuizQuestionAudio;

const NEW_INSTRUMENTS = [
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

// level title => storage path prefix (matches each level's own convention).
const LEVEL_PATH_PREFIXES = [
    'Basic Triads · Level 1' => 'basic-triads',
    'Basic Triads · Level 2' => 'basic-triads/level2',
    'Basic Triads · Level 3' => 'basic-triads/level3',
    'Basic Triads · Level 4' => 'basic-triads/level4',
    'Basic Triads · Level 5' => 'basic-triads/level5',
    'Basic Triads · Level 6' => 'basic-triads/level6',
    'Basic Triads · Level 7' => 'basic-triads/level7',
    'Basic Triads · Level 8' => 'basic-triads/level8',
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
