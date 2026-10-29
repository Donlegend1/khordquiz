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

// Octave lists are deliberately generous (1-7) for every instrument — the
// file-existence check in the attach loop below trims each one down to
// exactly its own real recorded range, so there's no need to hand-curate a
// cap per instrument here (and no risk of silently missing newly-available
// octaves after a note bank gets rebuilt with a wider real range).
$wideRange = [1, 2, 3, 4, 5, 6, 7];
$sounds = [
    'Piano' => ['slug' => 'piano', 'octaves' => $wideRange],
    'EP' => ['slug' => 'ep', 'octaves' => $wideRange],
    'Upright Piano' => ['slug' => 'upright-piano', 'octaves' => $wideRange],
    'Organ' => ['slug' => 'organ', 'octaves' => $wideRange],
    'Marimba' => ['slug' => 'marimba', 'octaves' => $wideRange],
    'Guitar' => ['slug' => 'guitar', 'octaves' => $wideRange],
    'Electric Guitar' => ['slug' => 'electric-guitar', 'octaves' => $wideRange],
    'Harpejji' => ['slug' => 'harpejji', 'octaves' => $wideRange],
    'Strings' => ['slug' => 'strings', 'octaves' => $wideRange],
    'Upright Bass' => ['slug' => 'upright-bass', 'octaves' => $wideRange],
    // Violin tested up to C7 but the GM sample got audibly synthetic/thin
    // that high — capped at C6.
    'Violin' => ['slug' => 'violin', 'octaves' => [4, 5, 6]],
    // C6 is altissimo register for alto sax (an advanced extended
    // technique, not an ordinary note) — accepted anyway per listening test.
    'Saxophone' => ['slug' => 'saxophone', 'octaves' => [3, 4, 5, 6]],
    'Voice' => ['slug' => 'voice', 'octaves' => [3, 4, 5, 6]],
    'Trombone' => ['slug' => 'trombone', 'octaves' => $wideRange],
    'Trumpet' => ['slug' => 'trumpet', 'octaves' => $wideRange],
    'Tuba' => ['slug' => 'tuba', 'octaves' => $wideRange],
    // Tropar Choir replaces Audience Choir in the catalog.
    'Tropar Choir' => ['slug' => 'tropar-choir', 'octaves' => $wideRange],
    'Ensemble Choir' => ['slug' => 'ensemble-choir', 'octaves' => $wideRange],
    'Sustained Choir' => ['slug' => 'sustained-choir', 'octaves' => $wideRange],
    'Steel Drum' => ['slug' => 'steel-drum', 'octaves' => $wideRange],
    'Flute' => ['slug' => 'flute', 'octaves' => $wideRange],
    'Clarinet' => ['slug' => 'clarinet', 'octaves' => $wideRange],
    'Oboe' => ['slug' => 'oboe', 'octaves' => $wideRange],
    'Bassoon' => ['slug' => 'bassoon', 'octaves' => $wideRange],
    'Cello' => ['slug' => 'cello', 'octaves' => $wideRange],
    'Celesta' => ['slug' => 'celesta', 'octaves' => $wideRange],
    'Harp' => ['slug' => 'harp', 'octaves' => $wideRange],
    'Jazz Guitar' => ['slug' => 'jazz-guitar', 'octaves' => $wideRange],
    'Vibraphone' => ['slug' => 'vibraphone', 'octaves' => $wideRange],
    'Bass Guitar' => ['slug' => 'bass-guitar', 'octaves' => $wideRange],
];

$degreeSlug = ['Do' => 'do', 'Re' => 're', 'Mi' => 'mi', 'Fa' => 'fa', 'So' => 'so', 'La' => 'la', 'Ti' => 'ti'];

$quiz = Quiz::where('title', 'Relative Tone Pitch #2')->firstOrFail();
$ids = json_decode(file_get_contents(__DIR__.'/relative_tone_pitch_2_question_ids.json'), true);

$attached = 0;
foreach ($ids as $octave => $byDegree) {
    foreach ($byDegree as $degree => $questionId) {
        $question = QuizQuestion::find($questionId);
        if (! $question) {
            continue;
        }

        foreach ($sounds as $soundLabel => $config) {
            if (! in_array((int) $octave, $config['octaves'], true)) {
                continue;
            }

            $filename = $octave == 3 ? "{$degreeSlug[$degree]}.wav" : "{$degreeSlug[$degree]}-c{$octave}.wav";
            $relativePath = "notes/{$config['slug']}/{$filename}";
            // The configured octave list is a ceiling, not a guarantee — an
            // instrument's note bank can be rebuilt with a narrower real
            // range (e.g. Guitar only has so-c2 through fa-c5, not the full
            // C2-C6 listed above), so skip anything not actually on disk
            // rather than attach a row pointing at a file that doesn't exist.
            if (! is_file(storage_path("app/public/{$relativePath}"))) {
                continue;
            }

            QuizQuestionAudio::updateOrCreate(
                ['quiz_question_id' => $question->id, 'sound' => $soundLabel],
                ['audio_path' => $relativePath]
            );
            $attached++;
        }
    }
}

echo "Attached {$attached} audio rows across {$quiz->questions->count()} questions.".PHP_EOL;
