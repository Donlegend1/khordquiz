<?php

// Builds "Secondary 9th Chords" General — all 12 qualities (6 from 9th
// Degree Chords + 6 from Secondary 9th Chords), all 12 keys, using the
// "two-hands, no-root" voicing (5 variants per quality, same technique as
// Level 5/6). Flat quality-only answer — matches the screenshot's existing
// 12-choice picker exactly, no inversion/position testing. No qualities
// excluded. 12 keys x 12 qualities x 5 variants = 720 rows — the admin
// shows exactly these 720 rows (not padded); QuizController::
// buildQuestionSequence() fair-shuffles a random 25 per attempt from the
// real pool. Rebuilds the quiz's questions and every instrument's attached
// audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const GENERAL_QUALITY_SLUGS = [
    'major9' => 'Major 9th', 'minor9' => 'Minor 9th', 'dim7add9' => 'Diminished 7(9)',
    'dominant9' => 'Dominant 9th', 'minor9b5' => 'Minor 9(♭5)', 'dom7b9' => 'Dominant 7(♭9)',
    'maj9s5' => 'Major 9(♯5)', 'dimmaj9' => 'Diminished-Major 9th', 'dom9b5' => 'Dominant 9(♭5)',
    'minmaj9' => 'Minor-Major 9th', 'aug9' => 'Augmented 9th', 'maj9b5' => 'Major 9(♭5)',
];

const GENERAL_VARIANTS = ['p1v1', 'p1v2', 'p2v1', 'p2v2', 'p2v3'];

const GENERAL_KEYS = [
    'c' => 'C', 'db' => 'Db', 'd' => 'D', 'eb' => 'Eb', 'e' => 'E', 'f' => 'F',
    'gb' => 'Gb', 'g' => 'G', 'ab' => 'Ab', 'a' => 'A', 'bb' => 'Bb', 'b' => 'B',
];

const GENERAL_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', 'General')->where('category', 'Secondary 9th Chords')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$allQualities = array_values(GENERAL_QUALITY_SLUGS);
$position = 0;
$created = 0;
$audioAttached = 0;

foreach (GENERAL_KEYS as $keySlug => $keyName) {
    foreach (GENERAL_QUALITY_SLUGS as $qualitySlug => $qualityLabel) {
        foreach (GENERAL_VARIANTS as $variantSlug) {
            $position++;

            $question = QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'position' => $position,
                'prompt' => 'Listen to the chord and identify its quality.',
                'choices' => $allQualities,
                'answer' => $qualityLabel,
            ]);

            foreach (GENERAL_INSTRUMENTS as $sound => $folderSlug) {
                QuizQuestionAudio::updateOrCreate(
                    ['quiz_question_id' => $question->id, 'sound' => $sound],
                    ['audio_path' => "secondary-ninth-chords/general/{$folderSlug}/{$keySlug}-{$qualitySlug}-{$variantSlug}.wav"]
                );
                $audioAttached++;
            }
            $created++;
        }
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of ".count(GENERAL_KEYS)." keys x 12 qualities x 5 variants; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(GENERAL_INSTRUMENTS)." instruments.".PHP_EOL;
