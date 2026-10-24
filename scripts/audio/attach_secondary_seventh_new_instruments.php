<?php

// Attaches Trombone, Saxophone, Marimba, Celesta, and Vibraphone audio to
// Secondary 7th Chords Levels 1-8 + General's EXISTING question rows (does
// not rebuild questions). For each question, reads Piano's existing
// audio_path and reuses its filename for each new instrument's own folder.

use App\Models\Quiz;
use App\Models\QuizQuestionAudio;

const NEW_INSTRUMENTS = [
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

const LEVEL_PATH_PREFIXES = [
    'Secondary 7th Chords · Level 1' => 'secondary-seventh-chords/level1',
    'Secondary 7th Chords · Level 2' => 'secondary-seventh-chords/level2',
    'Secondary 7th Chords · Level 3' => 'secondary-seventh-chords/level3',
    'Secondary 7th Chords · Level 4' => 'secondary-seventh-chords/level4',
    'Secondary 7th Chords · Level 5' => 'secondary-seventh-chords/level5',
    'Secondary 7th Chords · Level 6' => 'secondary-seventh-chords/level6',
    'Secondary 7th Chords · Level 7' => 'secondary-seventh-chords/level7',
    'Secondary 7th Chords · Level 8' => 'secondary-seventh-chords/level8',
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

// General — separate from the Level 1-8 loop since its DB title is
// literally "General" (scoped by category), not "... · Level 9".
$general = Quiz::where('title', 'General')->where('category', 'Secondary 7th Chords')->firstOrFail();
$attached = 0;
foreach ($general->questions as $question) {
    $pianoAudio = QuizQuestionAudio::where('quiz_question_id', $question->id)->where('sound', 'Piano')->first();
    if (!$pianoAudio) continue;
    $suffix = basename($pianoAudio->audio_path, '.wav');

    foreach (NEW_INSTRUMENTS as $sound => $folderSlug) {
        QuizQuestionAudio::updateOrCreate(
            ['quiz_question_id' => $question->id, 'sound' => $sound],
            ['audio_path' => "secondary-seventh-chords/level9/{$folderSlug}/{$suffix}.wav"]
        );
        $attached++;
    }
}
echo "Attached {$attached} rows on \"General\".".PHP_EOL;
