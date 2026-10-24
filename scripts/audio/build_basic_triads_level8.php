<?php

// Builds "Basic Triads · Level 8" — same shape as Level 6 (all 12 keys, C3
// through B3, all 3 inversions, compound quality+inversion answer composed
// as "{quality} · {inversion}"), but every voicing is in DROP-2 form
// (2nd-from-top note dropped an octave — chordalDrop2() in
// chordal_melodies_pool.php) based on C4 instead of Level 6's close-position
// C3 — same relationship Level 7 has to Level 5. Unlike every other Basic
// Triads level, this one deliberately excludes Augmented and Suspended —
// only Major, Minor and Diminished — per explicit instruction. 12 keys x 3
// qualities x 3 inversions = 108 rows — more than the quiz's question_count
// (25), same pool-larger-than-question_count pattern as Levels 2/4/6:
// QuizController::buildQuestionSequence() fair-shuffles a random 25 per
// attempt instead of hard-cycling a fixed subset. Rebuilds the quiz's
// questions and every instrument's attached audio from scratch every run.

require __DIR__.'/chordal_melodies_pool.php';

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

// Major/Minor/Diminished only — Augmented and Suspended deliberately excluded.
const BASIC_TRIADS_L8_QUALITY_INTERVALS = [
    'Major' => [0, 4, 7], 'Minor' => [0, 3, 7], 'Diminished' => [0, 3, 6],
];

const BASIC_TRIADS_L8_INVERSION_LABELS = ['root' => 'Root Position', '1st' => '1st Inversion', '2nd' => '2nd Inversion'];

// key slug => semitone offset from C4 (MIDI 60), chromatically through B4.
const BASIC_TRIADS_L8_KEYS = [
    'c' => 0, 'db' => 1, 'd' => 2, 'eb' => 3, 'e' => 4, 'f' => 5,
    'gb' => 6, 'g' => 7, 'ab' => 8, 'a' => 9, 'bb' => 10, 'b' => 11,
];

const BASIC_TRIADS_L8_BASE = 60; // C4

// sound => storage folder slug, matching Levels 1-7's naming.
const BASIC_TRIADS_L8_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

function basicTriadsL8Voicings(array $intervals, int $root): array
{
    $notes = array_map(fn ($i) => $root + $i, $intervals);
    sort($notes);
    [$n0, $n1, $n2] = $notes;

    return ['root' => [$n0, $n1, $n2], '1st' => [$n1, $n2, $n0 + 12], '2nd' => [$n2, $n0 + 12, $n1 + 12]];
}

$quiz = Quiz::where('title', 'Basic Triads · Level 8')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (BASIC_TRIADS_L8_KEYS as $keySlug => $keyOffset) {
    $root = BASIC_TRIADS_L8_BASE + $keyOffset;

    foreach (BASIC_TRIADS_L8_QUALITY_INTERVALS as $qualityLabel => $intervals) {
        $qualitySlug = strtolower($qualityLabel);
        foreach (BASIC_TRIADS_L8_INVERSION_LABELS as $invSlug => $invLabel) {
            $position++;
            $answer = $qualityLabel.' · '.$invLabel;

            $question = QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'position' => $position,
                'prompt' => 'Listen to the triad and identify its quality and inversion.',
                'choices' => [$answer],
                'answer' => $answer,
            ]);

            foreach (BASIC_TRIADS_L8_INSTRUMENTS as $sound => $folderSlug) {
                QuizQuestionAudio::updateOrCreate(
                    ['quiz_question_id' => $question->id, 'sound' => $sound],
                    ['audio_path' => "basic-triads/level8/{$folderSlug}/{$keySlug}-{$qualitySlug}-{$invSlug}.wav"]
                );
                $audioAttached++;
            }
            $created++;
        }
    }
}

// Sanity check the rendered files actually match chordalDrop2() — logged, not enforced.
foreach (BASIC_TRIADS_L8_KEYS as $keySlug => $keyOffset) {
    $root = BASIC_TRIADS_L8_BASE + $keyOffset;
    foreach (BASIC_TRIADS_L8_QUALITY_INTERVALS as $qualityLabel => $intervals) {
        $voicings = basicTriadsL8Voicings($intervals, $root);
        foreach ($voicings as $invSlug => $voicing) {
            $drop = chordalDrop2($voicing);
            echo "  {$keySlug}-".strtolower($qualityLabel)."-{$invSlug}: root position ".implode(',', $voicing).' -> drop-2 '.implode(',', $drop).PHP_EOL;
        }
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of ".count(BASIC_TRIADS_L8_KEYS)." keys x 3 qualities x 3 inversions; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(BASIC_TRIADS_L8_INSTRUMENTS)." instruments.".PHP_EOL;
