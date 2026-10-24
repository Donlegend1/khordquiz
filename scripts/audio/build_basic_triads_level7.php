<?php

// Builds "Basic Triads · Level 7" — same shape as Level 5 (key of C only,
// all 5 qualities x 3 inversions = 15 rows, compound quality+inversion
// answer composed as "{quality} · {inversion}" — Suspended is Sus2 only,
// not split), but every voicing is in DROP-2 form (2nd-from-top note dropped
// an octave — chordalDrop2() in chordal_melodies_pool.php, same transform
// Level 3 used for its single key) based on C4 (MIDI 60) instead of Level
// 5's close-position C3. A drop-2'd triad's quality and inversion are
// unchanged — only the voicing/register differs. Rebuilds the quiz's
// questions and Piano audio from scratch every run.

require __DIR__.'/chordal_melodies_pool.php';

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const BASIC_TRIADS_L7_QUALITY_INTERVALS = [
    'Major' => [0, 4, 7], 'Minor' => [0, 3, 7], 'Diminished' => [0, 3, 6],
    'Augmented' => [0, 4, 8], 'Suspended' => [0, 2, 7], // Sus2 only
];

const BASIC_TRIADS_L7_INVERSION_LABELS = ['root' => 'Root Position', '1st' => '1st Inversion', '2nd' => '2nd Inversion'];

const BASIC_TRIADS_L7_BASE = 60; // C4

// sound => storage folder slug, matching Levels 1-6's naming.
const BASIC_TRIADS_L7_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

function basicTriadsL7Voicings(array $intervals, int $root): array
{
    $notes = array_map(fn ($i) => $root + $i, $intervals);
    sort($notes);
    [$n0, $n1, $n2] = $notes;

    return ['root' => [$n0, $n1, $n2], '1st' => [$n1, $n2, $n0 + 12], '2nd' => [$n2, $n0 + 12, $n1 + 12]];
}

$quiz = Quiz::where('title', 'Basic Triads · Level 7')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (BASIC_TRIADS_L7_QUALITY_INTERVALS as $qualityLabel => $intervals) {
    $qualitySlug = strtolower($qualityLabel);
    $voicings = basicTriadsL7Voicings($intervals, BASIC_TRIADS_L7_BASE);

    foreach (BASIC_TRIADS_L7_INVERSION_LABELS as $invSlug => $invLabel) {
        $position++;
        $answer = $qualityLabel.' · '.$invLabel;

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'position' => $position,
            'prompt' => 'Listen to the triad and identify its quality and inversion.',
            'choices' => [$answer],
            'answer' => $answer,
        ]);

        foreach (BASIC_TRIADS_L7_INSTRUMENTS as $sound => $folderSlug) {
            QuizQuestionAudio::updateOrCreate(
                ['quiz_question_id' => $question->id, 'sound' => $sound],
                ['audio_path' => "basic-triads/level7/{$folderSlug}/{$qualitySlug}-{$invSlug}.wav"]
            );
            $audioAttached++;
        }
        $created++;
    }
}

// Sanity check the rendered files actually match chordalDrop2() — logged, not enforced.
foreach (BASIC_TRIADS_L7_QUALITY_INTERVALS as $qualityLabel => $intervals) {
    $voicings = basicTriadsL7Voicings($intervals, BASIC_TRIADS_L7_BASE);
    foreach ($voicings as $invSlug => $voicing) {
        $drop = chordalDrop2($voicing);
        echo '  '.strtolower($qualityLabel)."-{$invSlug}: root position ".implode(',', $voicing).' -> drop-2 '.implode(',', $drop).PHP_EOL;
    }
}

echo "Created {$created} rows on \"{$quiz->title}\", attached {$audioAttached} audio rows across ".count(BASIC_TRIADS_L7_INSTRUMENTS)." instruments.".PHP_EOL;
