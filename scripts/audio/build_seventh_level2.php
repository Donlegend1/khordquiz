<?php

// Builds "7th Degree Chords · Level 2" — same 5 qualities as Level 1, root
// position, but across ALL 12 keys instead of just C. 12 keys x 5 qualities
// = 60 rows — MORE than the quiz's question_count (25), same pool-larger-
// than-question_count pattern as Basic Triads Level 2:
// QuizController::buildQuestionSequence() fair-shuffles a random 25 per
// attempt instead of hard-cycling a fixed subset. Rebuilds the quiz's
// questions and every instrument's attached audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const SEVENTH_L2_QUALITIES = ['Diminished 7th', 'Dominant 7th', 'Minor 7(♭5)', 'Major 7th', 'Minor 7th'];

const SEVENTH_L2_VARIANTS = [
    ['Diminished 7th', 'diminished7'],
    ['Dominant 7th', 'dominant7'],
    ['Minor 7(♭5)', 'm7b5'],
    ['Major 7th', 'major7'],
    ['Minor 7th', 'minor7'],
];

const SEVENTH_L2_KEYS = [
    'c' => 'C', 'db' => 'Db', 'd' => 'D', 'eb' => 'Eb', 'e' => 'E', 'f' => 'F',
    'gb' => 'Gb', 'g' => 'G', 'ab' => 'Ab', 'a' => 'A', 'bb' => 'Bb', 'b' => 'B',
];

const SEVENTH_L2_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

$quiz = Quiz::where('title', '7th Degree Chords · Level 2')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (SEVENTH_L2_KEYS as $keySlug => $keyName) {
    foreach (SEVENTH_L2_VARIANTS as [$quality, $qualitySlug]) {
        $position++;
        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'position' => $position,
            'prompt' => 'Listen to the chord and identify its quality.',
            'choices' => SEVENTH_L2_QUALITIES,
            'answer' => $quality,
        ]);

        foreach (SEVENTH_L2_INSTRUMENTS as $sound => $folderSlug) {
            QuizQuestionAudio::updateOrCreate(
                ['quiz_question_id' => $question->id, 'sound' => $sound],
                ['audio_path' => "seventh-degree-chords/level2/{$folderSlug}/{$keySlug}-{$qualitySlug}.wav"]
            );
            $audioAttached++;
        }
        $created++;
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of ".count(SEVENTH_L2_KEYS)." keys x ".count(SEVENTH_L2_VARIANTS)." variants; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(SEVENTH_L2_INSTRUMENTS)." instruments.".PHP_EOL;
