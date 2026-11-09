<?php

// Builds "Chord Tone Recognition #6" — #2's 5 7th Degree Chord qualities
// PLUS #4's 6 Secondary 7th Chord qualities combined (all 11), 1st/2nd/3rd
// inversion, all 12 keys, each of the 4 chord tones (1/3/5/7) highlighted
// in turn. This is the original pre-split combined pool #2 held before it
// was divided into #2 (7th Degree only) and #4 (Secondary only) — kept
// here as its own quiz rather than reverting that split. Bass Guitar,
// Tuba, and Upright Bass are excluded (explicit request, same as every
// "Degree Chords" category). 12 keys x 11 qualities x 3 inversions x 4
// tones = 1584 rows — the admin shows exactly these 1584 rows (not
// padded); QuizController::buildQuestionSequence() fair-shuffles a random
// 25 per attempt from the real pool. Rebuilds the quiz's questions and
// every instrument's attached audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const CTR6_QUALITIES = [
    'diminished7' => 'Diminished 7th', 'dominant7' => 'Dominant 7th', 'm7b5' => 'Minor 7(♭5)',
    'major7' => 'Major 7th', 'minor7' => 'Minor 7th',
    'aug7' => 'Augmented 7th', 'minmaj7' => 'Minor-Major 7th', 'dimmaj7' => 'Diminished-Major 7th',
    'dom7b5' => 'Dominant 7(♭5)', 'maj7s5' => 'Major 7(♯5)', 'maj7b5' => 'Major 7(♭5)',
];

const CTR6_INVERSIONS = [1, 2, 3];
const CTR6_DEGREES = ['1', '3', '5', '7'];

const CTR6_KEYS = [
    'c' => 'C', 'db' => 'Db', 'd' => 'D', 'eb' => 'Eb', 'e' => 'E', 'f' => 'F',
    'gb' => 'Gb', 'g' => 'G', 'ab' => 'Ab', 'a' => 'A', 'bb' => 'Bb', 'b' => 'B',
];

const CTR6_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', 'Chord Tone Recognition #6')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (CTR6_KEYS as $keySlug => $keyName) {
    foreach (CTR6_QUALITIES as $qualitySlug => $qualityLabel) {
        foreach (CTR6_INVERSIONS as $inv) {
            foreach (CTR6_DEGREES as $degree) {
                $position++;

                $question = QuizQuestion::create([
                    'quiz_id' => $quiz->id,
                    'position' => $position,
                    'prompt' => "This is a {$qualityLabel} chord. Listen to the chord, then identify the chord tone that was highlighted.",
                    'choices' => CTR6_DEGREES,
                    'answer' => $degree,
                ]);

                foreach (CTR6_INSTRUMENTS as $sound => $folderSlug) {
                    QuizQuestionAudio::updateOrCreate(
                        ['quiz_question_id' => $question->id, 'sound' => $sound],
                        ['audio_path' => "chord-tone-recognition-6/{$folderSlug}/{$keySlug}-{$qualitySlug}-inv{$inv}-{$degree}.wav"]
                    );
                    $audioAttached++;
                }
                $created++;
            }
        }
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of ".count(CTR6_KEYS).' keys x '.count(CTR6_QUALITIES)." qualities x 3 inversions x 4 tones; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(CTR6_INSTRUMENTS).' instruments (Bass Guitar/Tuba/Upright Bass excluded).'.PHP_EOL;
