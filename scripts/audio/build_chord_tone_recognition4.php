<?php

// Builds "Chord Tone Recognition #4" — the same 6 Secondary 7th Chord
// qualities as #3 (split out of the old combined #2 — see
// build_chord_tone_recognition2.php), but using the 1st, 2nd, and 3rd
// inversions instead of root position, all 12 keys, each of the 4 chord
// tones (1/3/5/7) highlighted in turn. Same shape and audio format as #2,
// just this category's own folder (chord-tone-recognition-4/) and
// qualities. Bass Guitar, Tuba, and Upright Bass are excluded (explicit
// request, same as every "Degree Chords" category). 12 keys x 6 qualities
// x 3 inversions x 4 tones = 864 rows — the admin shows exactly these 864
// rows (not padded); QuizController::buildQuestionSequence() fair-shuffles
// a random 25 per attempt from the real pool. Rebuilds the quiz's
// questions and every instrument's attached audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const CTR4_QUALITIES = [
    'aug7' => 'Augmented 7th', 'minmaj7' => 'Minor-Major 7th', 'dimmaj7' => 'Diminished-Major 7th',
    'dom7b5' => 'Dominant 7(♭5)', 'maj7s5' => 'Major 7(♯5)', 'maj7b5' => 'Major 7(♭5)',
];

const CTR4_INVERSIONS = [1, 2, 3];
const CTR4_DEGREES = ['1', '3', '5', '7'];

const CTR4_KEYS = [
    'c' => 'C', 'db' => 'Db', 'd' => 'D', 'eb' => 'Eb', 'e' => 'E', 'f' => 'F',
    'gb' => 'Gb', 'g' => 'G', 'ab' => 'Ab', 'a' => 'A', 'bb' => 'Bb', 'b' => 'B',
];

const CTR4_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', 'Chord Tone Recognition #4')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (CTR4_KEYS as $keySlug => $keyName) {
    foreach (CTR4_QUALITIES as $qualitySlug => $qualityLabel) {
        foreach (CTR4_INVERSIONS as $inv) {
            foreach (CTR4_DEGREES as $degree) {
                $position++;

                $question = QuizQuestion::create([
                    'quiz_id' => $quiz->id,
                    'position' => $position,
                    'prompt' => "This is a {$qualityLabel} chord. Listen to the chord, then identify the chord tone that was highlighted.",
                    'choices' => CTR4_DEGREES,
                    'answer' => $degree,
                ]);

                foreach (CTR4_INSTRUMENTS as $sound => $folderSlug) {
                    QuizQuestionAudio::updateOrCreate(
                        ['quiz_question_id' => $question->id, 'sound' => $sound],
                        ['audio_path' => "chord-tone-recognition-4/{$folderSlug}/{$keySlug}-{$qualitySlug}-inv{$inv}-{$degree}.wav"]
                    );
                    $audioAttached++;
                }
                $created++;
            }
        }
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of ".count(CTR4_KEYS).' keys x '.count(CTR4_QUALITIES)." qualities x 3 inversions x 4 tones; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(CTR4_INSTRUMENTS).' instruments (Bass Guitar/Tuba/Upright Bass excluded).'.PHP_EOL;
