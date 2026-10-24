<?php

// Builds "13th Degree Chords · Level 3" — key of C only, flat quality-only
// answer. Left hand fixed at root-position triad; right hand plays the
// upper 4-note structure (7th/9th/11th/13th) through its 1st and 2nd
// inversions only (not 3rd) — see gen_thirteenth_level3_voicings.php. 2
// variants per quality x 4 qualities = 8 rows. The admin shows exactly
// these 8 rows. Rebuilds the quiz's questions and every instrument's
// attached audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const THIRTEENTH_L3_QUALITIES = ['Major 13(♯11)', 'Minor 13th', 'Dominant 13(♯11)', 'Dominant 13(♭9,♯11)'];

const THIRTEENTH_L3_SLUGS = [
    'Major 13(♯11)' => 'maj13s11', 'Minor 13th' => 'minor13',
    'Dominant 13(♯11)' => 'dom13s11', 'Dominant 13(♭9,♯11)' => 'dom13b9s11',
];

const THIRTEENTH_L3_VARIANTS = ['v1', 'v2'];

const THIRTEENTH_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

$quiz = Quiz::where('title', '13th Degree Chords · Level 3')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (THIRTEENTH_L3_QUALITIES as $quality) {
    $qualitySlug = THIRTEENTH_L3_SLUGS[$quality];

    foreach (THIRTEENTH_L3_VARIANTS as $variantSlug) {
        $position++;

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'position' => $position,
            'prompt' => 'Listen to the chord and identify its quality.',
            'choices' => THIRTEENTH_L3_QUALITIES,
            'answer' => $quality,
        ]);

        foreach (THIRTEENTH_INSTRUMENTS as $sound => $folderSlug) {
            QuizQuestionAudio::updateOrCreate(
                ['quiz_question_id' => $question->id, 'sound' => $sound],
                ['audio_path' => "thirteenth-degree-chords/level3/{$folderSlug}/{$qualitySlug}-{$variantSlug}.wav"]
            );
            $audioAttached++;
        }
        $created++;
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of 4 qualities x 2 variants; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(THIRTEENTH_INSTRUMENTS)." instruments.".PHP_EOL;
