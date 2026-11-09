<?php

// Builds "Chord Tone Recognition #3" — the 6 Secondary 7th Chord
// qualities (split out of the old combined #1 — see
// build_chord_tone_recognition1.php), root position, all 12 keys, each of
// the 4 chord tones (1/3/5/7) highlighted in turn. Same shape and audio
// format as #1, just this category's own folder
// (chord-tone-recognition-3/) and qualities. Bass Guitar, Tuba, and
// Upright Bass are excluded (explicit request, same as every "Degree
// Chords" category). 12 keys x 6 qualities x 4 tones = 288 rows — the
// admin shows exactly these 288 rows (not padded); QuizController::
// buildQuestionSequence() fair-shuffles a random 25 per attempt from the
// real pool. Rebuilds the quiz's questions and every instrument's attached
// audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const CTR3_QUALITIES = [
    'aug7' => 'Augmented 7th', 'minmaj7' => 'Minor-Major 7th', 'dimmaj7' => 'Diminished-Major 7th',
    'dom7b5' => 'Dominant 7(♭5)', 'maj7s5' => 'Major 7(♯5)', 'maj7b5' => 'Major 7(♭5)',
];

const CTR3_DEGREES = ['1', '3', '5', '7'];

const CTR3_KEYS = [
    'c' => 'C', 'db' => 'Db', 'd' => 'D', 'eb' => 'Eb', 'e' => 'E', 'f' => 'F',
    'gb' => 'Gb', 'g' => 'G', 'ab' => 'Ab', 'a' => 'A', 'bb' => 'Bb', 'b' => 'B',
];

const CTR3_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', 'Chord Tone Recognition #3')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (CTR3_KEYS as $keySlug => $keyName) {
    foreach (CTR3_QUALITIES as $qualitySlug => $qualityLabel) {
        foreach (CTR3_DEGREES as $degree) {
            $position++;

            $question = QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'position' => $position,
                'prompt' => "This is a {$qualityLabel} chord. Listen to the chord, then identify the chord tone that was highlighted.",
                'choices' => CTR3_DEGREES,
                'answer' => $degree,
            ]);

            foreach (CTR3_INSTRUMENTS as $sound => $folderSlug) {
                QuizQuestionAudio::updateOrCreate(
                    ['quiz_question_id' => $question->id, 'sound' => $sound],
                    ['audio_path' => "chord-tone-recognition-3/{$folderSlug}/{$keySlug}-{$qualitySlug}-{$degree}.wav"]
                );
                $audioAttached++;
            }
            $created++;
        }
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of ".count(CTR3_KEYS).' keys x '.count(CTR3_QUALITIES)." qualities x 4 tones; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(CTR3_INSTRUMENTS).' instruments (Bass Guitar/Tuba/Upright Bass excluded).'.PHP_EOL;
