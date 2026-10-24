<?php

// Builds "Chord Tone Recognition #1" — the 5 7th Degree Chord qualities +
// the 6 Secondary 7th Chord qualities, root position, all 12 keys, each of
// the 4 chord tones (1/3/5/7) highlighted in turn. The chord's quality is
// shown in the prompt text (per user decision) — only which tone was
// highlighted is tested. 12 keys x 11 qualities x 4 tones = 528 rows — the
// admin shows exactly these 528 rows (not padded); QuizController::
// buildQuestionSequence() fair-shuffles a random 25 per attempt from the
// real pool. Rebuilds the quiz's questions and every instrument's attached
// audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const CTR1_QUALITIES = [
    'diminished7' => 'Diminished 7th', 'dominant7' => 'Dominant 7th', 'm7b5' => 'Minor 7(♭5)',
    'major7' => 'Major 7th', 'minor7' => 'Minor 7th',
    'aug7' => 'Augmented 7th', 'minmaj7' => 'Minor-Major 7th', 'dimmaj7' => 'Diminished-Major 7th',
    'dom7b5' => 'Dominant 7(♭5)', 'maj7s5' => 'Major 7(♯5)', 'maj7b5' => 'Major 7(♭5)',
];

const CTR1_DEGREES = ['1', '3', '5', '7'];

const CTR1_KEYS = [
    'c' => 'C', 'db' => 'Db', 'd' => 'D', 'eb' => 'Eb', 'e' => 'E', 'f' => 'F',
    'gb' => 'Gb', 'g' => 'G', 'ab' => 'Ab', 'a' => 'A', 'bb' => 'Bb', 'b' => 'B',
];

const CTR1_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', 'Chord Tone Recognition #1')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (CTR1_KEYS as $keySlug => $keyName) {
    foreach (CTR1_QUALITIES as $qualitySlug => $qualityLabel) {
        foreach (CTR1_DEGREES as $degree) {
            $position++;

            $question = QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'position' => $position,
                'prompt' => "This is a {$qualityLabel} chord. Listen to the chord, then identify the chord tone that was highlighted.",
                'choices' => CTR1_DEGREES,
                'answer' => $degree,
            ]);

            foreach (CTR1_INSTRUMENTS as $sound => $folderSlug) {
                QuizQuestionAudio::updateOrCreate(
                    ['quiz_question_id' => $question->id, 'sound' => $sound],
                    ['audio_path' => "chord-tone-recognition-1/{$folderSlug}/{$keySlug}-{$qualitySlug}-{$degree}.wav"]
                );
                $audioAttached++;
            }
            $created++;
        }
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of ".count(CTR1_KEYS)." keys x ".count(CTR1_QUALITIES)." qualities x 4 tones; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(CTR1_INSTRUMENTS)." instruments.".PHP_EOL;
