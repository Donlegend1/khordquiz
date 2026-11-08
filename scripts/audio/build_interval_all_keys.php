<?php

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;
use App\Support\IntervalSamples;
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

// Expands each of the 6 "Intervals" category quizzes (Melodic/Harmonic
// Diatonic, Non-diatonic, General) from one fixed-register question per
// interval name to one question per playable key — see
// IntervalSamples::rootsForSemitones() for how "playable" is decided per
// instrument. Every new question reuses the exact same live-mix audio path
// convention (reference-intervals/live-v1/...) build_interval_live_mix.php
// already wired these quizzes to, so no new resolver code was needed, just
// more questions. The old single-key question per answer is replaced
// (deleted), not kept alongside the new ones — this app hasn't launched, and
// keeping it would just leave one key overrepresented.
$apply = in_array('--apply', $_SERVER['argv'] ?? [], true);

$quizzes = Quiz::where('category', 'Intervals')->with('questions')->get();

$report = [];
$plan = []; // quiz_id => ['old_ids' => [...], 'new_rows' => [...]]

foreach ($quizzes as $quiz) {
    $timing = str_starts_with($quiz->title, 'Harmonic') ? 'harmonic' : 'melodic';
    $byAnswer = $quiz->questions->groupBy('answer');

    $newRows = [];
    $oldIds = [];
    foreach ($byAnswer as $answer => $group) {
        $template = $group->first();
        $oldIds = [...$oldIds, ...$group->pluck('id')->all()];

        $semitones = IntervalSamples::SEMITONES[$answer] ?? null;
        if ($semitones === null) {
            throw new RuntimeException("Unknown interval answer: {$answer} in {$quiz->title}");
        }

        $perSoundRoots = [];
        foreach (QuizCatalog::SOUND_SLUGS as $sound => $slug) {
            $perSoundRoots[$sound] = IntervalSamples::rootsForSemitones($semitones, $slug);
        }
        $allPitchClasses = [];
        foreach ($perSoundRoots as $roots) {
            foreach (array_keys($roots) as $pc) {
                $allPitchClasses[$pc] = true;
            }
        }
        ksort($allPitchClasses);

        foreach (array_keys($allPitchClasses) as $pc) {
            $attachments = [];
            foreach ($perSoundRoots as $sound => $roots) {
                if (! isset($roots[$pc])) {
                    continue;
                }
                $slug = QuizCatalog::SOUND_SLUGS[$sound];
                $root = $roots[$pc];
                $second = $root + $semitones;
                $path = "reference-intervals/live-v1/{$timing}/{$slug}/{$root}-{$second}.wav";
                $notes = NoteSample::resolve($path);
                if ($notes === null || count($notes) !== 2) {
                    throw new RuntimeException("Unresolvable: {$path}");
                }
                $attachments[$sound] = $path;
            }
            if ($attachments === []) {
                throw new RuntimeException("No instrument covers pitch class {$pc} for {$answer} in {$quiz->title}");
            }
            $newRows[] = [
                'prompt' => $template->prompt,
                'answer' => $template->answer,
                'choices' => $template->choices,
                'attachments' => $attachments,
            ];
        }
    }

    $plan[$quiz->id] = ['title' => $quiz->title, 'old_ids' => $oldIds, 'new_rows' => $newRows];
    $report[] = $quiz->title.': '.count($oldIds).' old questions -> '.count($newRows).' new questions, '.
        array_sum(array_map(fn ($r) => count($r['attachments']), $newRows)).' instrument attachments';
}

echo implode("\n", $report).PHP_EOL;
echo 'TOTAL new questions: '.array_sum(array_map(fn ($p) => count($p['new_rows']), $plan)).PHP_EOL;

if (! $apply) {
    echo 'Dry run only — pass --apply to write.'.PHP_EOL;

    return;
}

DB::transaction(function () use ($plan): void {
    foreach ($plan as $quizId => $entry) {
        // Old rows deleted first — both sets occupy positions starting at 1,
        // and (quiz_id, position) is unique, so inserting before deleting
        // collides with whichever old row still holds that position.
        QuizQuestion::whereIn('id', $entry['old_ids'])->delete();
        foreach ($entry['new_rows'] as $index => $row) {
            $question = QuizQuestion::create([
                'quiz_id' => $quizId,
                'position' => $index + 1,
                'prompt' => $row['prompt'],
                'answer' => $row['answer'],
                'choices' => $row['choices'],
            ]);
            foreach ($row['attachments'] as $sound => $path) {
                QuizQuestionAudio::create([
                    'quiz_question_id' => $question->id,
                    'sound' => $sound,
                    'audio_path' => $path,
                ]);
            }
        }
    }
});

echo 'Applied.'.PHP_EOL;
