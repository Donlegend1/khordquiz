<?php

// Builds "Secondary 7th Chords · Level 3" — same shape as Level 1 (key of C
// only, 6 qualities, flat quality-only answer; the admin shows exactly
// these 6 rows, not padded up to question_count), but every chord is voiced
// in DROP-2 form (2nd-from-top note dropped an octave — chordalDrop2() in
// chordal_melodies_pool.php) based on C4 (MIDI 60) instead of Level 1's
// close-position C3. Rebuilds the quiz's questions and every instrument's
// attached audio from scratch every run.

require __DIR__.'/chordal_melodies_pool.php';

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const SEC7_L3_QUALITIES = ['Augmented 7th', 'Minor-Major 7th', 'Diminished-Major 7th', 'Dominant 7(♭5)', 'Major 7(♯5)', 'Major 7(♭5)'];

const SEC7_L3_VARIANTS = [
    ['Augmented 7th', 'aug7'],
    ['Minor-Major 7th', 'minmaj7'],
    ['Diminished-Major 7th', 'dimmaj7'],
    ['Dominant 7(♭5)', 'dom7b5'],
    ['Major 7(♯5)', 'maj7s5'],
    ['Major 7(♭5)', 'maj7b5'],
];

const SEC7_L3_INTERVALS = [
    'aug7' => [0, 4, 8, 10], 'minmaj7' => [0, 3, 7, 11], 'dimmaj7' => [0, 3, 6, 11],
    'dom7b5' => [0, 4, 6, 10], 'maj7s5' => [0, 4, 8, 11], 'maj7b5' => [0, 4, 6, 11],
];

const SEC7_L3_BASE = 60; // C4

const SEC7_L3_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

$quiz = Quiz::where('title', 'Secondary 7th Chords · Level 3')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$created = 0;
$audioAttached = 0;
$position = 0;
foreach (SEC7_L3_VARIANTS as [$quality, $slug]) {
    $position++;

    $question = QuizQuestion::create([
        'quiz_id' => $quiz->id,
        'position' => $position,
        'prompt' => 'Listen to the chord and identify its quality.',
        'choices' => SEC7_L3_QUALITIES,
        'answer' => $quality,
    ]);

    foreach (SEC7_L3_INSTRUMENTS as $sound => $folderSlug) {
        QuizQuestionAudio::updateOrCreate(
            ['quiz_question_id' => $question->id, 'sound' => $sound],
            ['audio_path' => "secondary-seventh-chords/level3/{$folderSlug}/{$slug}.wav"]
        );
        $audioAttached++;
    }
    $created++;
}

// Sanity check the rendered files actually match chordalDrop2() — logged, not enforced.
foreach (SEC7_L3_INTERVALS as $slug => $intervals) {
    $voicing = array_map(fn ($i) => SEC7_L3_BASE + $i, $intervals);
    $drop = chordalDrop2($voicing);
    echo "  {$slug}: root position ".implode(',', $voicing).' -> drop-2 '.implode(',', $drop).PHP_EOL;
}

echo "Created {$created} rows on \"{$quiz->title}\", attached {$audioAttached} audio rows across ".count(SEC7_L3_INSTRUMENTS)." instruments.".PHP_EOL;
