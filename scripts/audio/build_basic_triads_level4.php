<?php

// Builds "Basic Triads · Level 4" — same shape as Level 2 (12 keys x 6
// qualities = 72 rows, pool larger than question_count (25) so
// QuizController::buildQuestionSequence() fair-shuffles a random 25 per
// attempt), but every triad is voiced in DROP-2 form (2nd-from-top note
// dropped an octave — chordalDrop2() in chordal_melodies_pool.php, same
// transform Level 3 used for its single key) based on C4 through B4
// (MIDI 60-71) instead of Level 2's close-position C3-B3. A drop-2'd
// triad's quality is unchanged — only the voicing/register differs.
// Rebuilds the quiz's questions and every instrument's attached audio from
// scratch every run.

require __DIR__.'/chordal_melodies_pool.php';

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const BASIC_TRIADS_L4_QUALITIES = ['Major', 'Minor', 'Diminished', 'Augmented', 'Suspended'];

const BASIC_TRIADS_L4_ROOT_POSITION_INTERVALS = [
    'major' => [0, 4, 7], 'minor' => [0, 3, 7], 'diminished' => [0, 3, 6],
    'augmented' => [0, 4, 8], 'sus4' => [0, 5, 7], 'sus2' => [0, 2, 7],
];

// [answer quality, audio file slug suffix]
const BASIC_TRIADS_L4_VARIANTS = [
    ['Major', 'major'],
    ['Minor', 'minor'],
    ['Diminished', 'diminished'],
    ['Augmented', 'augmented'],
    ['Suspended', 'sus4'],
    ['Suspended', 'sus2'],
];

// key slug => semitone offset from C4 (MIDI 60), chromatically through B4.
const BASIC_TRIADS_L4_KEYS = [
    'c' => 0, 'db' => 1, 'd' => 2, 'eb' => 3, 'e' => 4, 'f' => 5,
    'gb' => 6, 'g' => 7, 'ab' => 8, 'a' => 9, 'bb' => 10, 'b' => 11,
];

const BASIC_TRIADS_L4_BASE = 60; // C4

// sound => storage folder slug, matching Levels 1-3's naming.
const BASIC_TRIADS_L4_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

$quiz = Quiz::where('title', 'Basic Triads · Level 4')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (BASIC_TRIADS_L4_KEYS as $keySlug => $keyOffset) {
    foreach (BASIC_TRIADS_L4_VARIANTS as [$quality, $qualitySlug]) {
        $position++;
        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'position' => $position,
            'prompt' => 'Listen to the triad and identify its quality.',
            'choices' => BASIC_TRIADS_L4_QUALITIES,
            'answer' => $quality,
        ]);

        foreach (BASIC_TRIADS_L4_INSTRUMENTS as $sound => $folderSlug) {
            QuizQuestionAudio::updateOrCreate(
                ['quiz_question_id' => $question->id, 'sound' => $sound],
                ['audio_path' => "basic-triads/level4/{$folderSlug}/{$keySlug}-{$qualitySlug}.wav"]
            );
            $audioAttached++;
        }
        $created++;
    }
}

// Sanity check the rendered files actually match chordalDrop2() — logged, not enforced.
foreach (BASIC_TRIADS_L4_KEYS as $keySlug => $keyOffset) {
    $root = BASIC_TRIADS_L4_BASE + $keyOffset;
    foreach (BASIC_TRIADS_L4_ROOT_POSITION_INTERVALS as $qualitySlug => $intervals) {
        $voicing = array_map(fn ($i) => $root + $i, $intervals);
        $drop = chordalDrop2($voicing);
        echo "  {$keySlug}-{$qualitySlug}: root position ".implode(',', $voicing).' -> drop-2 '.implode(',', $drop).PHP_EOL;
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of ".count(BASIC_TRIADS_L4_KEYS)." keys x ".count(BASIC_TRIADS_L4_VARIANTS)." variants; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(BASIC_TRIADS_L4_INSTRUMENTS)." instruments.".PHP_EOL;
