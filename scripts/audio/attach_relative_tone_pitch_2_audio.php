<?php

// Attaches the Do-Ti audio to "Relative Tone Pitch #2"'s 42-row pool (7 degrees x
// 6 octaves, C1-C6). Each instrument only gets audio for the octaves it can
// realistically play — buildQuestionSequence() already falls back to a sound's
// own audio-backed subset of the pool when it's smaller than the full 42, so a
// quiz attempt in Violin only ever draws from Violin's (smaller) real range.
//
// Octave 3 reuses Relative Tone Pitch #1's existing C3-octave files (same
// pitches) instead of re-rendering; octaves 1, 2, 4, 5, 6 are the new files
// from render_note_grid above.

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

$sounds = [
    'Piano' => ['slug' => 'piano', 'octaves' => [2, 3, 4, 5, 6]],
    'EP' => ['slug' => 'ep', 'octaves' => [2, 3, 4, 5, 6]],
    // Marimba's C7 is its real documented top (a standard concert marimba spans
    // C2-C7) — not a stretch like the others, just the instrument's full range.
    'Marimba' => ['slug' => 'marimba', 'octaves' => [2, 3, 4, 5, 6, 7]],
    'Guitar' => ['slug' => 'guitar', 'octaves' => [2, 3, 4, 5, 6]],
    'Jazz Guitar' => ['slug' => 'jazz-guitar', 'octaves' => [2, 3, 4, 5, 6]],
    // Violin tested up to C7 but the GM sample got audibly synthetic/thin that
    // high — capped at C6 instead.
    'Violin' => ['slug' => 'violin', 'octaves' => [4, 5, 6]],
    // C6 is altissimo register for alto sax (an advanced extended technique,
    // not an ordinary note) — accepted anyway per listening test.
    'Saxophone' => ['slug' => 'saxophone', 'octaves' => [3, 4, 5, 6]],
    'Voice' => ['slug' => 'voice', 'octaves' => [3, 4, 5, 6]],
];

$degreeSlug = ['Do' => 'do', 'Re' => 're', 'Mi' => 'mi', 'Fa' => 'fa', 'So' => 'so', 'La' => 'la', 'Ti' => 'ti'];

$quiz = Quiz::where('title', 'Relative Tone Pitch #2')->firstOrFail();
$ids = json_decode(file_get_contents(__DIR__.'/relative_tone_pitch_2_question_ids.json'), true);

$attached = 0;
foreach ($ids as $octave => $byDegree) {
    foreach ($byDegree as $degree => $questionId) {
        $question = QuizQuestion::find($questionId);
        if (!$question) continue;

        foreach ($sounds as $soundLabel => $config) {
            if (!in_array((int) $octave, $config['octaves'], true)) continue;

            $filename = $octave == 3 ? "{$degreeSlug[$degree]}.wav" : "{$degreeSlug[$degree]}-c{$octave}.wav";
            QuizQuestionAudio::updateOrCreate(
                ['quiz_question_id' => $question->id, 'sound' => $soundLabel],
                ['audio_path' => "notes/{$config['slug']}/{$filename}"]
            );
            $attached++;
        }
    }
}

echo "Attached {$attached} audio rows across {$quiz->questions->count()} questions.".PHP_EOL;
