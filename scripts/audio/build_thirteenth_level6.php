<?php

// Builds "13th Degree Chords · Level 6" — same shape as Level 5, but
// across ALL 12 keys. 12 keys x 4 qualities x 2 variants = 96 rows — the
// admin shows exactly these 96 rows (not padded); QuizController::
// buildQuestionSequence() fair-shuffles a random 25 per attempt from the
// real pool. Rebuilds the quiz's questions and every instrument's attached
// audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const THIRTEENTH_L6_QUALITIES = ['Major 13(♯11)', 'Minor 13th', 'Dominant 13(♯11)', 'Dominant 13(♭9,♯11)'];

const THIRTEENTH_L6_SLUGS = [
    'Major 13(♯11)' => 'maj13s11', 'Minor 13th' => 'minor13',
    'Dominant 13(♯11)' => 'dom13s11', 'Dominant 13(♭9,♯11)' => 'dom13b9s11',
];

const THIRTEENTH_L6_VARIANTS = ['v1', 'v2'];

const THIRTEENTH_L6_KEYS = [
    'c' => 'C', 'db' => 'Db', 'd' => 'D', 'eb' => 'Eb', 'e' => 'E', 'f' => 'F',
    'gb' => 'Gb', 'g' => 'G', 'ab' => 'Ab', 'a' => 'A', 'bb' => 'Bb', 'b' => 'B',
];

const THIRTEENTH_L6_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', '13th Degree Chords · Level 6')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (THIRTEENTH_L6_KEYS as $keySlug => $keyName) {
    foreach (THIRTEENTH_L6_QUALITIES as $quality) {
        $qualitySlug = THIRTEENTH_L6_SLUGS[$quality];

        foreach (THIRTEENTH_L6_VARIANTS as $variantSlug) {
            $position++;

            $question = QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'position' => $position,
                'prompt' => 'Listen to the chord and identify its quality.',
                'choices' => THIRTEENTH_L6_QUALITIES,
                'answer' => $quality,
            ]);

            foreach (THIRTEENTH_L6_INSTRUMENTS as $sound => $folderSlug) {
                QuizQuestionAudio::updateOrCreate(
                    ['quiz_question_id' => $question->id, 'sound' => $sound],
                    ['audio_path' => "thirteenth-degree-chords/level6/{$folderSlug}/{$keySlug}-{$qualitySlug}-{$variantSlug}.wav"]
                );
                $audioAttached++;
            }
            $created++;
        }
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of ".count(THIRTEENTH_L6_KEYS)." keys x 4 qualities x 2 variants; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(THIRTEENTH_L6_INSTRUMENTS)." instruments.".PHP_EOL;
