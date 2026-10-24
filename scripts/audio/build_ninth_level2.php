<?php

// Builds "9th Degree Chords · Level 2" — same 6 qualities as Level 1, root
// position, but across ALL 12 keys instead of just C. 12 keys x 6 qualities
// = 72 rows — MORE than the quiz's question_count (25), same pool-larger-
// than-question_count pattern used throughout. Rebuilds the quiz's
// questions and every instrument's attached audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const NINTH_L2_QUALITIES = ['Major 9th', 'Minor 9th', 'Diminished 7(9)', 'Dominant 9th', 'Minor 9(♭5)', 'Dominant 7(♭9)'];

const NINTH_L2_VARIANTS = [
    ['Major 9th', 'major9'],
    ['Minor 9th', 'minor9'],
    ['Diminished 7(9)', 'dim7add9'],
    ['Dominant 9th', 'dominant9'],
    ['Minor 9(♭5)', 'minor9b5'],
    ['Dominant 7(♭9)', 'dom7b9'],
];

const NINTH_L2_KEYS = [
    'c' => 'C', 'db' => 'Db', 'd' => 'D', 'eb' => 'Eb', 'e' => 'E', 'f' => 'F',
    'gb' => 'Gb', 'g' => 'G', 'ab' => 'Ab', 'a' => 'A', 'bb' => 'Bb', 'b' => 'B',
];

const NINTH_L2_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

$quiz = Quiz::where('title', '9th Degree Chords · Level 2')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (NINTH_L2_KEYS as $keySlug => $keyName) {
    foreach (NINTH_L2_VARIANTS as [$quality, $qualitySlug]) {
        $position++;
        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'position' => $position,
            'prompt' => 'Listen to the chord and identify its quality.',
            'choices' => NINTH_L2_QUALITIES,
            'answer' => $quality,
        ]);

        foreach (NINTH_L2_INSTRUMENTS as $sound => $folderSlug) {
            QuizQuestionAudio::updateOrCreate(
                ['quiz_question_id' => $question->id, 'sound' => $sound],
                ['audio_path' => "ninth-degree-chords/level2/{$folderSlug}/{$keySlug}-{$qualitySlug}.wav"]
            );
            $audioAttached++;
        }
        $created++;
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of ".count(NINTH_L2_KEYS)." keys x ".count(NINTH_L2_VARIANTS)." variants; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(NINTH_L2_INSTRUMENTS)." instruments.".PHP_EOL;
