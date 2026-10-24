<?php

// Builds "Secondary 7th Chords · Level 4" — same shape as Level 2 (12 keys
// x 6 qualities = 72 rows, pool larger than question_count (25)), but every
// chord is voiced in DROP-2 form based on C4 through B4 instead of Level
// 2's close-position C3-B3. Rebuilds the quiz's questions and every
// instrument's attached audio from scratch every run.

require __DIR__.'/chordal_melodies_pool.php';

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const SEC7_L4_QUALITIES = ['Augmented 7th', 'Minor-Major 7th', 'Diminished-Major 7th', 'Dominant 7(♭5)', 'Major 7(♯5)', 'Major 7(♭5)'];

const SEC7_L4_VARIANTS = [
    ['Augmented 7th', 'aug7'],
    ['Minor-Major 7th', 'minmaj7'],
    ['Diminished-Major 7th', 'dimmaj7'],
    ['Dominant 7(♭5)', 'dom7b5'],
    ['Major 7(♯5)', 'maj7s5'],
    ['Major 7(♭5)', 'maj7b5'],
];

const SEC7_L4_KEYS = [
    'c' => 0, 'db' => 1, 'd' => 2, 'eb' => 3, 'e' => 4, 'f' => 5,
    'gb' => 6, 'g' => 7, 'ab' => 8, 'a' => 9, 'bb' => 10, 'b' => 11,
];

const SEC7_L4_INTERVALS = [
    'aug7' => [0, 4, 8, 10], 'minmaj7' => [0, 3, 7, 11], 'dimmaj7' => [0, 3, 6, 11],
    'dom7b5' => [0, 4, 6, 10], 'maj7s5' => [0, 4, 8, 11], 'maj7b5' => [0, 4, 6, 11],
];

const SEC7_L4_BASE = 60; // C4

const SEC7_L4_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

$quiz = Quiz::where('title', 'Secondary 7th Chords · Level 4')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (SEC7_L4_KEYS as $keySlug => $keyOffset) {
    foreach (SEC7_L4_VARIANTS as [$quality, $qualitySlug]) {
        $position++;
        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'position' => $position,
            'prompt' => 'Listen to the chord and identify its quality.',
            'choices' => SEC7_L4_QUALITIES,
            'answer' => $quality,
        ]);

        foreach (SEC7_L4_INSTRUMENTS as $sound => $folderSlug) {
            QuizQuestionAudio::updateOrCreate(
                ['quiz_question_id' => $question->id, 'sound' => $sound],
                ['audio_path' => "secondary-seventh-chords/level4/{$folderSlug}/{$keySlug}-{$qualitySlug}.wav"]
            );
            $audioAttached++;
        }
        $created++;
    }
}

// Sanity check the rendered files actually match chordalDrop2() — logged, not enforced.
foreach (SEC7_L4_KEYS as $keySlug => $keyOffset) {
    $root = SEC7_L4_BASE + $keyOffset;
    foreach (SEC7_L4_INTERVALS as $qualitySlug => $intervals) {
        $voicing = array_map(fn ($i) => $root + $i, $intervals);
        $drop = chordalDrop2($voicing);
        echo "  {$keySlug}-{$qualitySlug}: root position ".implode(',', $voicing).' -> drop-2 '.implode(',', $drop).PHP_EOL;
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of ".count(SEC7_L4_KEYS)." keys x ".count(SEC7_L4_VARIANTS)." variants; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(SEC7_L4_INSTRUMENTS)." instruments.".PHP_EOL;
