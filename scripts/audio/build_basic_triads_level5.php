<?php

// Builds "Basic Triads · Level 5" — a different answer shape from Levels
// 1-4: this is the "compound" quality+inversion picker (COMPOUND_ANSWER_PRESETS
// in resources/js/lib/quizAnswerPresets.js — qualities: TRIAD_CHOICES,
// inversions: TRIAD_INVERSIONS), composed client-side as "{quality} · {inversion}"
// (AnswerBuilder.jsx's CompoundChoices), so the stored answer must match that
// exact format for grading (QuizController::answer() does a plain string ===).
// Key of C only, all 5 qualities x 3 inversions (root/1st/2nd) = 15 rows.
// Unlike Levels 1-4, Suspended is NOT split into Sus2/Sus4 here — just Sus2,
// one quality like the other four. Rebuilds the quiz's questions and Piano
// audio from scratch every run.

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const BASIC_TRIADS_L5_INVERSION_LABELS = ['root' => 'Root Position', '1st' => '1st Inversion', '2nd' => '2nd Inversion'];

// quality => [root-position voicing from C3 (MIDI 48), 1st-inversion voicing, 2nd-inversion voicing].
// Each inversion moves the bottom N notes of root position up an octave —
// standard triad inversion, not chordalDrop2 (that's a jazz voicing technique,
// a different transform from classical inversions).
const BASIC_TRIADS_L5_VOICINGS = [
    'Major' => ['root' => [48, 52, 55], '1st' => [52, 55, 60], '2nd' => [55, 60, 64]],
    'Minor' => ['root' => [48, 51, 55], '1st' => [51, 55, 60], '2nd' => [55, 60, 63]],
    'Diminished' => ['root' => [48, 51, 54], '1st' => [51, 54, 60], '2nd' => [54, 60, 63]],
    'Augmented' => ['root' => [48, 52, 56], '1st' => [52, 56, 60], '2nd' => [56, 60, 64]],
    'Suspended' => ['root' => [48, 50, 55], '1st' => [50, 55, 60], '2nd' => [55, 60, 62]], // Sus2 only
];

// sound => storage folder slug, matching Levels 1-4's naming. Bass Guitar and
// Violin got real audio an octave away from everyone else's register (see
// the render script) — real instrument-range reasons, not a typo: Violin's
// lowest open string is G3, below this quiz's C3 root, so its audio was
// rendered a full octave up; Bass Guitar's audio was rendered a full octave
// down so the triads sit in a part of its range basslines actually use,
// instead of unusually high. Every other instrument shares Piano's register.
const BASIC_TRIADS_L5_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

$quiz = Quiz::where('title', 'Basic Triads · Level 5')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (BASIC_TRIADS_L5_VOICINGS as $quality => $inversions) {
    foreach ($inversions as $slug => $notes) {
        $position++;
        $answer = $quality.' · '.BASIC_TRIADS_L5_INVERSION_LABELS[$slug];

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'position' => $position,
            'prompt' => 'Listen to the triad and identify its quality and inversion.',
            'choices' => [$answer],
            'answer' => $answer,
        ]);

        foreach (BASIC_TRIADS_L5_INSTRUMENTS as $sound => $folderSlug) {
            QuizQuestionAudio::updateOrCreate(
                ['quiz_question_id' => $question->id, 'sound' => $sound],
                ['audio_path' => "basic-triads/level5/{$folderSlug}/".strtolower($quality)."-{$slug}.wav"]
            );
            $audioAttached++;
        }
        $created++;
    }
}

echo "Created {$created} rows on \"{$quiz->title}\", attached {$audioAttached} audio rows across ".count(BASIC_TRIADS_L5_INSTRUMENTS)." instruments.".PHP_EOL;
