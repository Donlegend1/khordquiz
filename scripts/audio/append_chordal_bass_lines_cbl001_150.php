<?php

// Appends CBL-001 through CBL-150 (all of chordalBassLineLiteralProgressions(),
// the full set of 150 NEW bass-first candidates from docs/composition/
// chordal-bass-line-150-candidates.{md,json}, added 6 Nov 2026) as new rows
// onto "Chordal Bass Lines" ONLY — independent content, not derived from and
// not shared with the "Chordal melodies" pool/quiz, which this script never
// touches. Each audio path is melodic-dictation/chordal-bass-lines/{slug}/
// cbl{n}.wav (see the new NoteSample::resolve() branch), attaching every
// instrument whose real recorded range actually covers the sequence's final
// drop2 voicing — not a fixed instrument list, and never a per-note
// substitution. Idempotent: skips any CBL index that already has a row (the
// pilot run already applied CBL-001-010; this run adds CBL-011-150). Dry run
// by default; pass --apply to write.

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
require_once __DIR__.'/chordal_bass_lines_pool.php';

const CBL_BASS_MIN = 36; // handoff's agreed bass register floor
const CBL_BASS_MAX = 48; // handoff's agreed bass register ceiling ("app C4")

$apply = in_array('--apply', $_SERVER['argv'] ?? [], true);

$bassLines = Quiz::where('title', 'Chordal Bass Lines')->firstOrFail();

$allProgressions = chordalBassLineLiteralProgressions();
$newIndices = range(1, 150); // CBL-001..CBL-150, 1-based to match the cbl{n}.wav path
if (count($allProgressions) !== 150) {
    throw new RuntimeException('Expected 150 total progressions, found '.count($allProgressions).'.');
}

// Idempotency guard: a CBL row's audio always points at a
// chordal-bass-lines/*/cbl{n}.wav path — if any such path is already
// attached to a question, skip that index instead of duplicating it.
$existingCblIndices = [];
foreach (QuizQuestionAudio::query()->where('audio_path', 'like', 'melodic-dictation/chordal-bass-lines/%')->pluck('audio_path') as $path) {
    if (preg_match('#/cbl(\d+)\.wav$#', $path, $m)) {
        $existingCblIndices[(int) $m[1]] = true;
    }
}

$bassPosition = (int) $bassLines->questions()->max('position');

$plan = [];
foreach ($newIndices as $n) {
    if (isset($existingCblIndices[$n])) {
        echo "cbl{$n}: already present — skipping.\n";

        continue;
    }

    $steps = $allProgressions[$n - 1];
    $voicings = chordalDrop2Voicings(chordalBuildVoicings($steps));

    $basses = array_map(fn ($v) => min($v), $voicings);
    foreach ($basses as $bass) {
        if ($bass < CBL_BASS_MIN || $bass > CBL_BASS_MAX) {
            throw new RuntimeException("cbl{$n}: bass note {$bass} outside the agreed {CBL_BASS_MIN}-{CBL_BASS_MAX} register — refusing to silently shift it.");
        }
    }
    $label = chordalStripOctaves(implode(' ', array_map(fn ($b) => chordalLabelForTop($b), $basses)));

    $attachments = [];
    foreach (QuizCatalog::SOUND_SLUGS as $sound => $slug) {
        $path = "melodic-dictation/chordal-bass-lines/{$slug}/cbl{$n}.wav";
        if (NoteSample::resolve($path) !== null) {
            $attachments[$sound] = $path;
        }
    }
    if ($attachments === []) {
        throw new RuntimeException("cbl{$n}: no instrument resolves this voicing at all.");
    }

    $plan[] = ['n' => $n, 'label' => $label, 'attachments' => $attachments];
}

echo 'Chordal Bass Lines: '.count($plan).' new rows, instrument counts: '
    .implode(',', array_map(fn ($r) => count($r['attachments']), $plan))."\n";

if (! $apply) {
    echo "Dry run only — pass --apply to write.\n";

    return;
}

DB::transaction(function () use ($plan, $bassLines, &$bassPosition): void {
    foreach ($plan as $row) {
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

echo 'Applied: '.count($plan)." Chordal Bass Lines rows.\n";
