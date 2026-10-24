<?php

// Builds "Chord Tone Recognition #2" — same 11 qualities as #1, but using
// the 1st, 2nd, and 3rd inversions instead of root position, all 12 keys,
// each of the 4 chord tones (1/3/5/7) highlighted in turn. The chord's
// quality is shown in the prompt text; only which tone was highlighted is
// tested (never the inversion). 12 keys x 11 qualities x 3 inversions x 4
// tones = 1584 rows — the admin shows exactly these 1584 rows (not
// padded); QuizController::buildQuestionSequence() fair-shuffles a random
// 25 per attempt from the real pool. Rebuilds the quiz's questions and
// every instrument's attached audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const CTR2_QUALITIES = [
    'diminished7' => 'Diminished 7th', 'dominant7' => 'Dominant 7th', 'm7b5' => 'Minor 7(♭5)',
    'major7' => 'Major 7th', 'minor7' => 'Minor 7th',
    'aug7' => 'Augmented 7th', 'minmaj7' => 'Minor-Major 7th', 'dimmaj7' => 'Diminished-Major 7th',
    'dom7b5' => 'Dominant 7(♭5)', 'maj7s5' => 'Major 7(♯5)', 'maj7b5' => 'Major 7(♭5)',
];

const CTR2_INVERSIONS = [1, 2, 3];
const CTR2_DEGREES = ['1', '3', '5', '7'];

const CTR2_KEYS = [
    'c' => 'C', 'db' => 'Db', 'd' => 'D', 'eb' => 'Eb', 'e' => 'E', 'f' => 'F',
    'gb' => 'Gb', 'g' => 'G', 'ab' => 'Ab', 'a' => 'A', 'bb' => 'Bb', 'b' => 'B',
];

const CTR2_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', 'Chord Tone Recognition #2')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (CTR2_KEYS as $keySlug => $keyName) {
    foreach (CTR2_QUALITIES as $qualitySlug => $qualityLabel) {
        foreach (CTR2_INVERSIONS as $inv) {
            foreach (CTR2_DEGREES as $degree) {
                $position++;

                $question = QuizQuestion::create([
                    'quiz_id' => $quiz->id,
                    'position' => $position,
                    'prompt' => "This is a {$qualityLabel} chord. Listen to the chord, then identify the chord tone that was highlighted.",
                    'choices' => CTR2_DEGREES,
                    'answer' => $degree,
                ]);

                foreach (CTR2_INSTRUMENTS as $sound => $folderSlug) {
                    QuizQuestionAudio::updateOrCreate(
                        ['quiz_question_id' => $question->id, 'sound' => $sound],
                        ['audio_path' => "chord-tone-recognition-2/{$folderSlug}/{$keySlug}-{$qualitySlug}-inv{$inv}-{$degree}.wav"]
                    );
                    $audioAttached++;
                }
                $created++;
            }
        }
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of ".count(CTR2_KEYS)." keys x ".count(CTR2_QUALITIES)." qualities x 3 inversions x 4 tones; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(CTR2_INSTRUMENTS)." instruments.".PHP_EOL;
