<?php

// Builds "7th Degree Chords · Level 3" — same shape as Level 1 (key of C
// only, 5 qualities, flat quality-only answer; the admin shows exactly
// these 5 rows, not padded up to question_count), but every chord is voiced
// in DROP-2 form (2nd-from-top note dropped an octave — chordalDrop2() in
// chordal_melodies_pool.php) based on C4 (MIDI 60) instead of Level 1's
// close-position C3 — same relationship Basic Triads Level 3 has to Level
// 1. Rebuilds the quiz's questions and every instrument's attached audio
// from scratch every run.

require __DIR__.'/chordal_melodies_pool.php';

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const SEVENTH_L3_QUALITIES = ['Diminished 7th', 'Dominant 7th', 'Minor 7(♭5)', 'Major 7th', 'Minor 7th'];

const SEVENTH_L3_VARIANTS = [
    ['Diminished 7th', 'diminished7'],
    ['Dominant 7th', 'dominant7'],
    ['Minor 7(♭5)', 'm7b5'],
    ['Major 7th', 'major7'],
    ['Minor 7th', 'minor7'],
];

const SEVENTH_L3_INTERVALS = [
    'diminished7' => [0, 3, 6, 9], 'dominant7' => [0, 4, 7, 10],
    'm7b5' => [0, 3, 6, 10], 'major7' => [0, 4, 7, 11], 'minor7' => [0, 3, 7, 10],
];

const SEVENTH_L3_BASE = 60; // C4

const SEVENTH_L3_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

$quiz = Quiz::where('title', '7th Degree Chords · Level 3')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$created = 0;
$audioAttached = 0;
$position = 0;
foreach (SEVENTH_L3_VARIANTS as [$quality, $slug]) {
    $position++;

    $question = QuizQuestion::create([
        'quiz_id' => $quiz->id,
        'position' => $position,
        'prompt' => 'Listen to the chord and identify its quality.',
        'choices' => SEVENTH_L3_QUALITIES,
        'answer' => $quality,
    ]);

    foreach (SEVENTH_L3_INSTRUMENTS as $sound => $folderSlug) {
        QuizQuestionAudio::updateOrCreate(
            ['quiz_question_id' => $question->id, 'sound' => $sound],
            ['audio_path' => "seventh-degree-chords/level3/{$folderSlug}/{$slug}.wav"]
        );
        $audioAttached++;
    }
    $created++;
}

// Sanity check the rendered files actually match chordalDrop2() — logged, not enforced.
foreach (SEVENTH_L3_INTERVALS as $slug => $intervals) {
    $voicing = array_map(fn ($i) => SEVENTH_L3_BASE + $i, $intervals);
    $drop = chordalDrop2($voicing);
    echo "  {$slug}: root position ".implode(',', $voicing).' -> drop-2 '.implode(',', $drop).PHP_EOL;
}

echo "Created {$created} rows on \"{$quiz->title}\", attached {$audioAttached} audio rows across ".count(SEVENTH_L3_INSTRUMENTS)." instruments.".PHP_EOL;
