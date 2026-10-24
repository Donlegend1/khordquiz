<?php

// Builds "11th Degree Chords · Level 3" — key of C only, flat quality-only
// answer. Left hand fixed at root-position triad; right hand plays either
// the upper triad (7th/9th/11th, 2 inversions) or the upper 7th chord
// (5th/7th/9th/11th, 3 inversions) — see gen_eleventh_level3_voicings.php.
// 5 variants per quality x 6 qualities = 30 rows. The admin shows exactly
// these 30 rows (not padded); QuizController::buildQuestionSequence()
// fair-shuffles a random 25 per attempt from the real pool. Rebuilds the
// quiz's questions and every instrument's attached audio from scratch
// every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const ELEVENTH_L3_QUALITIES = ['Major 9(♯11)', 'Minor 11th', 'Diminished 7(9,11)', 'Dominant 9(♯11)', 'Minor 11(♭5)', 'Dominant 7(♭9,♯11)'];

const ELEVENTH_L3_SLUGS = [
    'Major 9(♯11)' => 'maj9s11', 'Minor 11th' => 'minor11', 'Diminished 7(9,11)' => 'dim7911',
    'Dominant 9(♯11)' => 'dom9s11', 'Minor 11(♭5)' => 'minor11b5', 'Dominant 7(♭9,♯11)' => 'dom7b9s11',
];

const ELEVENTH_L3_VARIANTS = ['pAv1', 'pAv2', 'pBv1', 'pBv2', 'pBv3'];

const ELEVENTH_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', '11th Degree Chords · Level 3')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (ELEVENTH_L3_QUALITIES as $quality) {
    $qualitySlug = ELEVENTH_L3_SLUGS[$quality];

    foreach (ELEVENTH_L3_VARIANTS as $variantSlug) {
        $position++;

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'position' => $position,
            'prompt' => 'Listen to the chord and identify its quality.',
            'choices' => ELEVENTH_L3_QUALITIES,
            'answer' => $quality,
        ]);

        foreach (ELEVENTH_INSTRUMENTS as $sound => $folderSlug) {
            QuizQuestionAudio::updateOrCreate(
                ['quiz_question_id' => $question->id, 'sound' => $sound],
                ['audio_path' => "eleventh-degree-chords/level3/{$folderSlug}/{$qualitySlug}-{$variantSlug}.wav"]
            );
            $audioAttached++;
        }
        $created++;
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of 6 qualities x 5 variants; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(ELEVENTH_INSTRUMENTS)." instruments.".PHP_EOL;
