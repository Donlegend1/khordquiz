<?php

// Builds "Basic Triads · Level 3" — same shape as Level 1 (6 distinct
// audios: Major, Minor, Diminished, Augmented, Sus4, Sus2 — Sus2/Sus4 both
// answer "Suspended"; the admin shows exactly these 6 rows, not padded up
// to question_count), key of C only, but each
// triad is voiced in DROP-2 form (2nd-from-top note dropped an octave —
// chordalDrop2() in chordal_melodies_pool.php, same transform Chordal Bass
// Lines uses) based on C4 (MIDI 60) instead of Level 1's close-position C3.
// A drop-2'd triad's quality is unchanged (same 3 notes, just one octave
// down) — only the voicing/register differs from Level 1. Rebuilds the
// quiz's questions and Piano audio from scratch every run.

require __DIR__.'/chordal_melodies_pool.php';

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const BASIC_TRIADS_L3_QUALITIES = ['Major', 'Minor', 'Diminished', 'Augmented', 'Suspended'];

const BASIC_TRIADS_L3_ROOT_POSITION_INTERVALS = [
    'major' => [0, 4, 7], 'minor' => [0, 3, 7], 'diminished' => [0, 3, 6],
    'augmented' => [0, 4, 8], 'sus4' => [0, 5, 7], 'sus2' => [0, 2, 7],
];

// [answer quality, audio file slug] — cycled in this order across the rows,
// same order as Level 1.
const BASIC_TRIADS_L3_VARIANTS = [
    ['Major', 'major'],
    ['Minor', 'minor'],
    ['Diminished', 'diminished'],
    ['Augmented', 'augmented'],
    ['Suspended', 'sus4'],
    ['Suspended', 'sus2'],
];

const BASIC_TRIADS_L3_BASE = 60; // C4

// sound => storage folder slug, matching Levels 1/2's naming.
const BASIC_TRIADS_L3_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

$quiz = Quiz::where('title', 'Basic Triads · Level 3')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$created = 0;
$audioAttached = 0;
$position = 0;
foreach (BASIC_TRIADS_L3_VARIANTS as [$quality, $slug]) {
    $position++;

    $question = QuizQuestion::create([
        'quiz_id' => $quiz->id,
        'position' => $position,
        'prompt' => 'Listen to the triad and identify its quality.',
        'choices' => BASIC_TRIADS_L3_QUALITIES,
        'answer' => $quality,
    ]);

    foreach (BASIC_TRIADS_L3_INSTRUMENTS as $sound => $folderSlug) {
        QuizQuestionAudio::updateOrCreate(
            ['quiz_question_id' => $question->id, 'sound' => $sound],
            ['audio_path' => "basic-triads/level3/{$folderSlug}/{$slug}.wav"]
        );
        $audioAttached++;
    }
    $created++;
}

// Sanity check the rendered files actually match chordalDrop2() — logged, not enforced.
foreach (BASIC_TRIADS_L3_ROOT_POSITION_INTERVALS as $slug => $intervals) {
    $voicing = array_map(fn ($i) => BASIC_TRIADS_L3_BASE + $i, $intervals);
    $drop = chordalDrop2($voicing);
    echo "  {$slug}: root position ".implode(',', $voicing).' -> drop-2 '.implode(',', $drop).PHP_EOL;
}

echo "Created {$created} rows on \"{$quiz->title}\", attached {$audioAttached} audio rows across ".count(BASIC_TRIADS_L3_INSTRUMENTS)." instruments.".PHP_EOL;
