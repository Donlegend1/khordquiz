<?php

// Builds "9th Degree Chords · Level 4" — same shape as Level 3 (root fixed
// in bass, upper-structure 7th chord inverted through all 4 positions,
// flat quality-only answer), but across ALL 12 keys instead of just C. 12
// keys x 6 qualities x 4 positions = 288 rows — MORE than the quiz's
// question_count (25), same pool-larger-than-question_count pattern used
// throughout. Rebuilds the quiz's questions and every instrument's
// attached audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const NINTH_L4_QUALITIES = ['Major 9th', 'Minor 9th', 'Diminished 7(9)', 'Dominant 9th', 'Minor 9(♭5)', 'Dominant 7(♭9)'];

const NINTH_L4_QUALITY_SLUGS = [
    'Major 9th' => 'major9', 'Minor 9th' => 'minor9', 'Diminished 7(9)' => 'dim7add9',
    'Dominant 9th' => 'dominant9', 'Minor 9(♭5)' => 'minor9b5', 'Dominant 7(♭9)' => 'dom7b9',
];

const NINTH_L4_POSITIONS = ['root', '1st', '2nd', '3rd'];

const NINTH_L4_KEYS = [
    'c' => 'C', 'db' => 'Db', 'd' => 'D', 'eb' => 'Eb', 'e' => 'E', 'f' => 'F',
    'gb' => 'Gb', 'g' => 'G', 'ab' => 'Ab', 'a' => 'A', 'bb' => 'Bb', 'b' => 'B',
];

const NINTH_L4_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

$quiz = Quiz::where('title', '9th Degree Chords · Level 4')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (NINTH_L4_KEYS as $keySlug => $keyName) {
    foreach (NINTH_L4_QUALITIES as $qualityLabel) {
        $qualitySlug = NINTH_L4_QUALITY_SLUGS[$qualityLabel];

        foreach (NINTH_L4_POSITIONS as $posSlug) {
            $position++;

            $question = QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'position' => $position,
                'prompt' => 'Listen to the chord and identify its quality.',
                'choices' => NINTH_L4_QUALITIES,
                'answer' => $qualityLabel,
            ]);

            foreach (NINTH_L4_INSTRUMENTS as $sound => $folderSlug) {
                QuizQuestionAudio::updateOrCreate(
                    ['quiz_question_id' => $question->id, 'sound' => $sound],
                    ['audio_path' => "ninth-degree-chords/level4/{$folderSlug}/{$keySlug}-{$qualitySlug}-{$posSlug}.wav"]
                );
                $audioAttached++;
            }
            $created++;
        }
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of ".count(NINTH_L4_KEYS)." keys x 6 qualities x 4 positions; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(NINTH_L4_INSTRUMENTS)." instruments.".PHP_EOL;
