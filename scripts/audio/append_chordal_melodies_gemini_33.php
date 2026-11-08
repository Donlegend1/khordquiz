<?php

// Appends the 33 new Gemini-generated progressions (indices 26-58 of
// chordalLiteralProgressions(), added 6 Nov 2026) as new rows onto both
// "Chordal melodies" and "Chordal Bass Lines", without touching any
// existing row. Attaches every instrument whose real recorded range
// actually covers each voicing (checked via NoteSample::resolve(), same
// as everywhere else in this app) rather than a fixed instrument list.
// Dry run by default; pass --apply to write.

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;
use App\Support\NoteSample;
use App\Support\QuizCatalog;
use Illuminate\Container\Container;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/../../vendor/autoload.php';
if (! Container::getInstance()->bound('db')) {
    $app = require __DIR__.'/../../bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
}
config(['cache.default' => 'array']);
require_once __DIR__.'/chordal_melodies_pool.php';

const BASS_LINE_MAX_NOTE = 48; // same cap replicate_chordal_to_bass_line.php uses — app's "C4"

$apply = in_array('--apply', $_SERVER['argv'] ?? [], true);

$melodies = Quiz::where('title', 'Chordal melodies')->firstOrFail();
$bassLines = Quiz::where('title', 'Chordal Bass Lines')->firstOrFail();

$allProgressions = chordalLiteralProgressions();
$newIndices = range(26, count($allProgressions) - 1);
if (count($newIndices) !== 33) {
    throw new RuntimeException('Expected 33 new progressions at indices 26-58, found '.count($newIndices).'.');
}

$melodyPosition = (int) $melodies->questions()->max('position');
$bassPosition = (int) $bassLines->questions()->max('position');

$plan = ['melodies' => [], 'bassLines' => []];

foreach ($newIndices as $i) {
    $steps = $allProgressions[$i];
    $voicings = chordalBuildVoicings($steps);

    $tops = array_map(fn ($v) => $v[count($v) - 1], $voicings);
    $melodyLabel = chordalStripOctaves(implode(' ', array_map('chordalLabelForTop', $tops)));

    $melodyAttachments = [];
    foreach (QuizCatalog::SOUND_SLUGS as $sound => $slug) {
        $path = "melodic-dictation/chordal-melodies/{$slug}/p{$i}-orig.wav";
        if (NoteSample::resolve($path) !== null) {
            $melodyAttachments[$sound] = $path;
        }
    }
    if ($melodyAttachments === []) {
        throw new RuntimeException("p{$i}: no instrument resolves the original voicing at all.");
    }
    $plan['melodies'][] = ['i' => $i, 'label' => $melodyLabel, 'attachments' => $melodyAttachments];

    $dropVoicings = chordalDrop2Voicings($voicings);
    $bassNotes = array_map(fn ($v) => $v[0], $dropVoicings);
    if (max($bassNotes) > BASS_LINE_MAX_NOTE) {
        echo "p{$i}: skipping Chordal Bass Lines row — bass reaches ".max($bassNotes).' (above '.BASS_LINE_MAX_NOTE.").\n";

        continue;
    }
    $bassLabel = chordalStripOctaves(implode(' ', array_map('chordalLabelForTop', $bassNotes)));

    $bassAttachments = [];
    foreach (QuizCatalog::SOUND_SLUGS as $sound => $slug) {
        $path = "melodic-dictation/chordal-melodies/{$slug}/p{$i}-drop2.wav";
        if (NoteSample::resolve($path) !== null) {
            $bassAttachments[$sound] = $path;
        }
    }
    if ($bassAttachments === []) {
        echo "p{$i}: no instrument resolves the drop2 voicing — skipping Chordal Bass Lines row.\n";

        continue;
    }
    $plan['bassLines'][] = ['i' => $i, 'label' => $bassLabel, 'attachments' => $bassAttachments];
}

echo 'Chordal melodies: '.count($plan['melodies']).' new rows, instrument counts: '
    .implode(',', array_map(fn ($r) => count($r['attachments']), $plan['melodies']))."\n";
echo 'Chordal Bass Lines: '.count($plan['bassLines']).' new rows, instrument counts: '
    .implode(',', array_map(fn ($r) => count($r['attachments']), $plan['bassLines']))."\n";

if (! $apply) {
    echo "Dry run only — pass --apply to write.\n";

    return;
}

DB::transaction(function () use ($plan, $melodies, $bassLines, &$melodyPosition, &$bassPosition): void {
    foreach ($plan['melodies'] as $row) {
        $melodyPosition++;
        $question = QuizQuestion::create([
            'quiz_id' => $melodies->id,
            'position' => $melodyPosition,
            'prompt' => 'Tap the melody (top note of each chord) in the order you hear it.',
            'choices' => [$row['label']],
            'answer' => $row['label'],
        ]);
        foreach ($row['attachments'] as $sound => $path) {
            QuizQuestionAudio::create(['quiz_question_id' => $question->id, 'sound' => $sound, 'audio_path' => $path]);
        }
    }
    foreach ($plan['bassLines'] as $row) {
        $bassPosition++;
        $question = QuizQuestion::create([
            'quiz_id' => $bassLines->id,
            'position' => $bassPosition,
            'prompt' => 'Tap the bass line (bottom note of each chord) in the order you hear it.',
            'choices' => [$row['label']],
            'answer' => $row['label'],
        ]);
        foreach ($row['attachments'] as $sound => $path) {
            QuizQuestionAudio::create(['quiz_question_id' => $question->id, 'sound' => $sound, 'audio_path' => $path]);
        }
    }
});

echo 'Applied: '.count($plan['melodies']).' Chordal melodies rows, '.count($plan['bassLines'])." Chordal Bass Lines rows.\n";
