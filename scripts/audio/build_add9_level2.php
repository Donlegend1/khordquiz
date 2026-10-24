<?php

// Builds "Add 9 · Level 2" — same 4 qualities as Level 1 (Major, Minor,
// Diminished, Augmented add9, close-position voicing), but across ALL 12
// keys instead of just C. 12 keys x 4 qualities = 48 rows — MORE than the
// quiz's question_count (25), same pool-larger-than-question_count pattern
// as Basic Triads Level 2: QuizController::buildQuestionSequence() already
// fair-shuffles a pool larger than question_count with no repeats within a
// lap, so each attempt draws 25 of the 48 key/quality combos at random.
// Rebuilds the quiz's questions and every instrument's attached audio from
// scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const ADD9_L2_QUALITIES = ['Major add 9', 'Minor add 9', 'Diminished add 9', 'Augmented add 9'];

// [answer quality, audio file slug suffix]
const ADD9_L2_VARIANTS = [
    ['Major add 9', 'major'],
    ['Minor add 9', 'minor'],
    ['Diminished add 9', 'diminished'],
    ['Augmented add 9', 'augmented'],
];

// key slug => display name, chromatically through all 12.
const ADD9_L2_KEYS = [
    'c' => 'C', 'db' => 'Db', 'd' => 'D', 'eb' => 'Eb', 'e' => 'E', 'f' => 'F',
    'gb' => 'Gb', 'g' => 'G', 'ab' => 'Ab', 'a' => 'A', 'bb' => 'Bb', 'b' => 'B',
];

// sound => storage folder slug, matching Level 1's naming.
const ADD9_L2_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

$quiz = Quiz::where('title', 'Add 9 · Level 2')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (ADD9_L2_KEYS as $keySlug => $keyName) {
    foreach (ADD9_L2_VARIANTS as [$quality, $qualitySlug]) {
        $position++;
        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'position' => $position,
            'prompt' => 'Listen to the chord and identify its quality.',
            'choices' => ADD9_L2_QUALITIES,
            'answer' => $quality,
        ]);

        foreach (ADD9_L2_INSTRUMENTS as $sound => $folderSlug) {
            QuizQuestionAudio::updateOrCreate(
                ['quiz_question_id' => $question->id, 'sound' => $sound],
                ['audio_path' => "add9/level2/{$folderSlug}/{$keySlug}-{$qualitySlug}.wav"]
            );
            $audioAttached++;
        }
        $created++;
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of ".count(ADD9_L2_KEYS)." keys x ".count(ADD9_L2_VARIANTS)." variants; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(ADD9_L2_INSTRUMENTS)." instruments.".PHP_EOL;
