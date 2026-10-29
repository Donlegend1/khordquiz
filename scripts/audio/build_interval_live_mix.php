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
$manifestPath = storage_path('app/private/interval_live_mix_v1.json');

/** Prepare first, render and verify the manifest, then --apply attaches only verified files. */
if (in_array('--apply', $_SERVER['argv'] ?? [], true)) {
    $manifest = json_decode(file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
    $receipt = json_decode(file_get_contents($manifestPath.'.verified.json'), true, flags: JSON_THROW_ON_ERROR);
    if ($receipt['manifest_sha256'] !== hash_file('sha256', $manifestPath)) {
        throw new RuntimeException('Manifest changed after audio verification.');
    }
    foreach ($receipt['files'] as $path => $hash) {
        if (! is_file(storage_path('app/public/'.$path)) || hash_file('sha256', storage_path('app/public/'.$path)) !== $hash) {
            throw new RuntimeException('Verified audio changed or missing: '.$path);
        }
    }
    foreach ($receipt['sources'] as $path => $hash) {
        if (hash_file('sha256', storage_path('app/public/'.$path)) !== $hash) {
            throw new RuntimeException('Source sample changed after verification: '.$path);
        }
    }
    if (count($receipt['files']) !== count($manifest['files'])) {
        throw new RuntimeException('Not every render was verified.');
    }
    DB::transaction(function () use ($manifest): void {
        foreach ($manifest['attachments'] as $entry) {
            $question = QuizQuestion::with('quiz')->findOrFail($entry['question_id']);
            if ($question->quiz->category !== 'Intervals' || $question->answer !== $entry['answer']) {
                throw new RuntimeException('Question changed during generation.');
            }
            $current = $question->audioVariants()->where('sound', $entry['sound'])->first();
            if ($current?->audio_path === $entry['output_path']) {
                continue;
            }
            if ($current?->audio_path !== $entry['previous_path']) {
                throw new RuntimeException('Attachment changed during generation.');
            }
            if (NoteSample::resolve($entry['output_path']) === null) {
                throw new RuntimeException('Live Mix cannot resolve '.$entry['output_path']);
            }
            QuizQuestionAudio::updateOrCreate(
                ['quiz_question_id' => $entry['question_id'], 'sound' => $entry['sound']],
                ['audio_path' => $entry['output_path']],
            );
        }
    });
    echo 'Attached '.count($manifest['attachments']).' verified interval variants; original audio files preserved.'.PHP_EOL;

    return;
}

$quizzes = Quiz::where('category', 'Intervals')->with('questions.audioVariants')->get();
$expectedTitles = QuizCatalog::subcategories('Intervals');
if ($quizzes->pluck('title')->sort()->values()->all() !== collect($expectedTitles)->sort()->values()->all()) {
    throw new RuntimeException('Expected exactly the six catalog Intervals quizzes.');
}
$files = [];
$attachments = [];
$pairCache = [];
$before = [];
foreach ($quizzes as $quiz) {
    $timing = str_starts_with($quiz->title, 'Harmonic') ? 'harmonic' : 'melodic';
    if ($quiz->questions->isEmpty()) {
        throw new RuntimeException('Empty interval quiz: '.$quiz->title);
    }
    foreach ($quiz->questions as $question) {
        if (! in_array($question->answer, IntervalSamples::answersFor($quiz->title), true)
            || ! in_array($question->answer, $question->choices, true)) {
            throw new RuntimeException('Invalid interval answer on question '.$question->id);
        }
        foreach (QuizCatalog::SOUND_SLUGS as $sound => $slug) {
            $key = $slug.'|'.$question->answer;
            if (! array_key_exists($key, $pairCache)) {
                $pairCache[$key] = IntervalSamples::pairFor($question->answer, $slug);
            }
            $pair = $pairCache[$key];
            if ($pair === null || IntervalSamples::SEMITONES[$question->answer] !== $pair[1] - $pair[0]) {
                throw new RuntimeException("No exact recorded pair: {$sound}, {$question->answer}");
            }
            $path = "reference-intervals/live-v1/{$timing}/{$slug}/{$pair[0]}-{$pair[1]}.wav";
            if (! isset($files[$path])) {
                $notes = NoteSample::resolve($path);
                if ($notes === null || count($notes) !== 2) {
                    throw new RuntimeException('Unresolvable pair: '.$path);
                }
                $files[$path] = [
                    'output_path' => $path,
                    'answer' => $question->answer,
                    'timing' => $timing,
                    'midi' => $pair,
                    'notes' => array_map(fn ($note, $index) => [
                        'path' => NoteSample::path($note['slug'], $note['degree'], $note['octave']),
                        'offset' => $note['offset'],
                        'midi' => $pair[$index],
                    ], $notes, [0, 1]),
                ];
            }
            $current = $question->audioVariants->firstWhere('sound', $sound);
            $attachments[] = [
                'question_id' => $question->id,
                'answer' => $question->answer,
                'sound' => $sound,
                'output_path' => $path,
                'previous_path' => $current?->audio_path,
            ];
        }
        $before[] = ['question_id' => $question->id, 'answer' => $question->answer, 'choices' => $question->choices,
            'variants' => $question->audioVariants->map(fn ($row) => ['id' => $row->id, 'sound' => $row->sound, 'path' => $row->audio_path])->all()];
    }
}
file_put_contents($manifestPath.'.before.json', json_encode($before, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
file_put_contents($manifestPath, json_encode(['files' => array_values($files), 'attachments' => $attachments], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo 'Prepared '.$quizzes->count().' quizzes, '.count($before).' questions, '.count($attachments).' instrument attachments, '.count($files).' unique audio files.'.PHP_EOL;
echo $manifestPath.PHP_EOL;
