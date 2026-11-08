<?php

use App\Models\Quiz;
use App\Models\QuizQuestionAudio;
use App\Support\QuizCatalog;
use App\Support\TwoFiveOneExtensionSamples;
use Illuminate\Support\Facades\DB;

if (PHP_SAPI === 'cli' && ! app()->runningUnitTests()) {
    ini_set('memory_limit', '512M');
}

config(['cache.default' => 'array']);
$title = TwoFiveOneExtensionSamples::TITLE;
$prefix = TwoFiveOneExtensionSamples::PREFIX;
$exportName = 'two-five-one-extensions';
$prompt = 'Identify the quality and all extensions of each chord in 2–5–1. All three chords are in root position, in any of the 12 keys.';
$description = '60 curated 2–5–1 extension patterns per key across all 12 keys. All 27 approved extension chords are represented within the allowed qualities on 2, 5, and 1. All chords use their basic root-position structures with no doubled or octave-displaced tones. Instrument versions outside comfortable registers are excluded.';
$manifest = TwoFiveOneExtensionSamples::manifest();
if (count($manifest) !== 720) {
    throw new RuntimeException('Every curated pattern must have a playable version in all 12 keys.');
}
DB::transaction(function () use ($manifest, $title, $prefix, $prompt, $description, $exportName): void {
    $quiz = Quiz::where('title', $title)->firstOrFail();
    if (! $quiz->wasRecentlyCreated && ! app()->runningUnitTests()) {
        $backup = [
            'quiz' => $quiz->getAttributes(),
            'questions' => $quiz->questions()->get()->map->getAttributes()->all(),
            'audio' => QuizQuestionAudio::whereIn('quiz_question_id', $quiz->questions()->select('id'))->get()->map->getAttributes()->all(),
            'attempts' => $quiz->attempts()->get()->map->getAttributes()->all(),
            'answers' => DB::table('quiz_attempt_answers')->whereIn('quiz_attempt_id', $quiz->attempts()->select('id'))->get()->all(),
        ];
        $path = storage_path('app/private/'.$exportName.'-before-live-mix-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json');
        if (file_put_contents($path, json_encode($backup, JSON_THROW_ON_ERROR)) === false) {
            throw new RuntimeException('Could not save 2-5-1 extension backup.');
        }
        echo 'Saved backup: '.$path.PHP_EOL;
    }
    $quiz->attempts()->delete();
    $quiz->questions()->delete();
    foreach (array_values($manifest) as $index => $row) {
        $question = $quiz->questions()->create([
            'position' => $index + 1,
            'prompt' => $prompt,
            'choices' => [], 'answer' => $row['answer'],
        ]);
        foreach ($row['samples'] as $slug => $notes) {
            $question->audioVariants()->create(['sound' => array_search($slug, QuizCatalog::SOUND_SLUGS, true), 'audio_path' => $prefix.'/'.$slug.'/'.$row['recipe'].'.wav']);
        }
    }
    $quiz->update(['title' => $title, 'question_count' => 25, 'description' => $description]);
});
if (! app()->runningUnitTests()) {
    file_put_contents(storage_path('app/private/'.$exportName.'.json'), json_encode($manifest, JSON_THROW_ON_ERROR));
}
echo 'Created '.count($manifest).' key/quality sequences and '.array_sum(array_map(fn (array $row): int => count($row['samples']), $manifest)).' instrument mixes.'.PHP_EOL;
