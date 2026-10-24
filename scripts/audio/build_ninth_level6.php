<?php

// Builds "9th Degree Chords · Level 6" — same "two-hands, no-root" voicing
// as Level 5 (flat quality-only answer), but across ALL 12 keys instead of
// just C. 12 keys x 6 qualities x 5 variants = 360 rows — the admin shows
// exactly these 360 rows (NOT padded/cycled); QuizController::
// buildQuestionSequence() fair-shuffles a random 25 per attempt from the
// real pool. Rebuilds the quiz's questions and every instrument's attached
// audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const NINTH_L6_QUALITIES = ['Major 9th', 'Minor 9th', 'Diminished 7(9)', 'Dominant 9th', 'Minor 9(♭5)', 'Dominant 7(♭9)'];

const NINTH_L6_QUALITY_SLUGS = [
    'Major 9th' => 'major9', 'Minor 9th' => 'minor9', 'Diminished 7(9)' => 'dim7add9',
    'Dominant 9th' => 'dominant9', 'Minor 9(♭5)' => 'minor9b5', 'Dominant 7(♭9)' => 'dom7b9',
];

const NINTH_L6_VARIANTS = ['p1v1', 'p1v2', 'p2v1', 'p2v2', 'p2v3'];

const NINTH_L6_KEYS = [
    'c' => 'C', 'db' => 'Db', 'd' => 'D', 'eb' => 'Eb', 'e' => 'E', 'f' => 'F',
    'gb' => 'Gb', 'g' => 'G', 'ab' => 'Ab', 'a' => 'A', 'bb' => 'Bb', 'b' => 'B',
];

const NINTH_L6_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', '9th Degree Chords · Level 6')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (NINTH_L6_KEYS as $keySlug => $keyName) {
    foreach (NINTH_L6_QUALITIES as $qualityLabel) {
        $qualitySlug = NINTH_L6_QUALITY_SLUGS[$qualityLabel];

        foreach (NINTH_L6_VARIANTS as $variantSlug) {
            $position++;

            $question = QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'position' => $position,
                'prompt' => 'Listen to the chord and identify its quality.',
                'choices' => NINTH_L6_QUALITIES,
                'answer' => $qualityLabel,
            ]);

            foreach (NINTH_L6_INSTRUMENTS as $sound => $folderSlug) {
                QuizQuestionAudio::updateOrCreate(
                    ['quiz_question_id' => $question->id, 'sound' => $sound],
                    ['audio_path' => "ninth-degree-chords/level6/{$folderSlug}/{$keySlug}-{$qualitySlug}-{$variantSlug}.wav"]
                );
                $audioAttached++;
            }
            $created++;
        }
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of ".count(NINTH_L6_KEYS)." keys x 6 qualities x 5 variants; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(NINTH_L6_INSTRUMENTS)." instruments.".PHP_EOL;
