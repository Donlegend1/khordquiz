<?php

// Builds "Add 9 · Level 6" — same shape as Level 2 (12 keys x 4 qualities =
// 48 rows, flat quality-only answer, pool larger than question_count (25)
// so QuizController::buildQuestionSequence() fair-shuffles a random 25 per
// attempt), but every chord is voiced in DROP-2 form (2nd-from-top note
// dropped an octave — chordalDrop2() in chordal_melodies_pool.php) based on
// C4 through B4 (MIDI 60-71) instead of Level 2's close-position C3-B3 —
// same relationship Level 5 has to Level 1. Rebuilds the quiz's questions
// and Piano audio from scratch every run.

require __DIR__.'/chordal_melodies_pool.php';

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const ADD9_L6_QUALITIES = ['Major add 9', 'Minor add 9', 'Diminished add 9', 'Augmented add 9'];

const ADD9_L6_VARIANTS = [
    ['Major add 9', 'major'],
    ['Minor add 9', 'minor'],
    ['Diminished add 9', 'diminished'],
    ['Augmented add 9', 'augmented'],
];

const ADD9_L6_INTERVALS = [
    'major' => [0, 2, 4, 7], 'minor' => [0, 2, 3, 7],
    'diminished' => [0, 2, 3, 6], 'augmented' => [0, 2, 4, 8],
];

// key slug => semitone offset from C4 (MIDI 60), chromatically through B4.
const ADD9_L6_KEYS = [
    'c' => 0, 'db' => 1, 'd' => 2, 'eb' => 3, 'e' => 4, 'f' => 5,
    'gb' => 6, 'g' => 7, 'ab' => 8, 'a' => 9, 'bb' => 10, 'b' => 11,
];

const ADD9_L6_BASE = 60; // C4

// sound => storage folder slug, matching Levels 1-5's naming.
const ADD9_L6_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

$quiz = Quiz::where('title', 'Add 9 · Level 6')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (ADD9_L6_KEYS as $keySlug => $keyOffset) {
    foreach (ADD9_L6_VARIANTS as [$quality, $qualitySlug]) {
        $position++;
        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'position' => $position,
            'prompt' => 'Listen to the chord and identify its quality.',
            'choices' => ADD9_L6_QUALITIES,
            'answer' => $quality,
        ]);

        foreach (ADD9_L6_INSTRUMENTS as $sound => $folderSlug) {
            QuizQuestionAudio::updateOrCreate(
                ['quiz_question_id' => $question->id, 'sound' => $sound],
                ['audio_path' => "add9/level6/{$folderSlug}/{$keySlug}-{$qualitySlug}.wav"]
            );
            $audioAttached++;
        }
        $created++;
    }
}

// Sanity check the rendered files actually match chordalDrop2() — logged, not enforced.
foreach (ADD9_L6_KEYS as $keySlug => $keyOffset) {
    $root = ADD9_L6_BASE + $keyOffset;
    foreach (ADD9_L6_INTERVALS as $qualitySlug => $intervals) {
        $voicing = array_map(fn ($i) => $root + $i, $intervals);
        $drop = chordalDrop2($voicing);
        echo "  {$keySlug}-{$qualitySlug}: root position ".implode(',', $voicing).' -> drop-2 '.implode(',', $drop).PHP_EOL;
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of ".count(ADD9_L6_KEYS)." keys x ".count(ADD9_L6_VARIANTS)." variants; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(ADD9_L6_INSTRUMENTS)." instruments.".PHP_EOL;
