<?php

// Attaches real-sample audio rows for the piano-family instruments across
// every Melodic Dictation quiz:
// - Upright Piano: brand new everywhere, mirrors every existing Piano row's
//   path (swap the "piano" folder segment for "upright-piano").
// - EP / Organ: already have rows + paths for the 5 quizzes that had them
//   (3-Note Stepwise, 3-Note with Skips, 5-Note Melodies, Dyad Melodies,
//   Dual-tonic melodies) — those just needed their WAV files replaced in
//   place (no DB change). Chordal melodies and Chordal Bass Lines never had
//   EP/Organ at all, so this also mirrors Piano's rows there the same way
//   as Upright Piano.

use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;
use App\Models\Quiz;

const MELODIC_DICTATION_TITLES = [
    '3-Note Stepwise', '3-Note with Skips', '5-Note Melodies',
    'Dyad Melodies', 'Dual-tonic melodies', 'Chordal melodies', 'Chordal Bass Lines',
];

$quizIds = Quiz::whereIn('title', MELODIC_DICTATION_TITLES)->pluck('id');
$questionIds = QuizQuestion::whereIn('quiz_id', $quizIds)->pluck('id');

$pianoRows = QuizQuestionAudio::where('sound', 'Piano')
    ->whereIn('quiz_question_id', $questionIds)
    ->get();

$attached = ['Upright Piano' => 0, 'EP' => 0, 'Organ' => 0];

foreach ($pianoRows as $row) {
    foreach (['Upright Piano' => 'upright-piano', 'EP' => 'ep', 'Organ' => 'organ'] as $sound => $folderSlug) {
        $newPath = preg_replace('#/piano/#', "/{$folderSlug}/", $row->audio_path);

        QuizQuestionAudio::updateOrCreate(
            ['quiz_question_id' => $row->quiz_question_id, 'sound' => $sound],
            ['audio_path' => $newPath]
        );
        $attached[$sound]++;
    }
}

foreach ($attached as $sound => $count) {
    echo "{$sound}: attached/updated {$count} rows.".PHP_EOL;
}
