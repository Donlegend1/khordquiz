<?php

// Builds "11th Degree Chords · Level 6" — same "avoid the root" voicing as
// Level 5, but across ALL 12 keys. 12 keys x 6 qualities x 8 variants =
// 576 rows — the admin shows exactly these 576 rows (not padded);
// QuizController::buildQuestionSequence() fair-shuffles a random 25 per
// attempt from the real pool. Rebuilds the quiz's questions and every
// instrument's attached audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const ELEVENTH_L6_QUALITIES = ['Major 9(♯11)', 'Minor 11th', 'Diminished 7(9,11)', 'Dominant 9(♯11)', 'Minor 11(♭5)', 'Dominant 7(♭9,♯11)'];

const ELEVENTH_L6_SLUGS = [
    'Major 9(♯11)' => 'maj9s11', 'Minor 11th' => 'minor11', 'Diminished 7(9,11)' => 'dim7911',
    'Dominant 9(♯11)' => 'dom9s11', 'Minor 11(♭5)' => 'minor11b5', 'Dominant 7(♭9,♯11)' => 'dom7b9s11',
];

const ELEVENTH_L6_VARIANTS = ['tt1', 'tt2', 'ts1', 'ts2', 'st1', 'st2', 'ss1', 'ss2'];

const ELEVENTH_L6_KEYS = [
    'c' => 'C', 'db' => 'Db', 'd' => 'D', 'eb' => 'Eb', 'e' => 'E', 'f' => 'F',
    'gb' => 'Gb', 'g' => 'G', 'ab' => 'Ab', 'a' => 'A', 'bb' => 'Bb', 'b' => 'B',
];

const ELEVENTH_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', '11th Degree Chords · Level 6')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (ELEVENTH_L6_KEYS as $keySlug => $keyName) {
    foreach (ELEVENTH_L6_QUALITIES as $quality) {
        $qualitySlug = ELEVENTH_L6_SLUGS[$quality];

        foreach (ELEVENTH_L6_VARIANTS as $variantSlug) {
            $position++;

            $question = QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'position' => $position,
                'prompt' => 'Listen to the chord and identify its quality.',
                'choices' => ELEVENTH_L6_QUALITIES,
                'answer' => $quality,
            ]);

            foreach (ELEVENTH_INSTRUMENTS as $sound => $folderSlug) {
                QuizQuestionAudio::updateOrCreate(
                    ['quiz_question_id' => $question->id, 'sound' => $sound],
                    ['audio_path' => "eleventh-degree-chords/level6/{$folderSlug}/{$keySlug}-{$qualitySlug}-{$variantSlug}.wav"]
                );
                $audioAttached++;
            }
            $created++;
        }
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of ".count(ELEVENTH_L6_KEYS)." keys x 6 qualities x 8 variants; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(ELEVENTH_INSTRUMENTS)." instruments.".PHP_EOL;
