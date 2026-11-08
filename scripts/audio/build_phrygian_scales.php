<?php

use App\Models\Quiz;
use App\Models\QuizQuestionAudio;
use App\Support\PhrygianScaleAllKeysSamples;
use App\Support\PhrygianScaleSamples;
use App\Support\QuizCatalog;
use Illuminate\Support\Facades\DB;

config(['cache.default' => 'array']);
$sources = [PhrygianScaleSamples::class, PhrygianScaleAllKeysSamples::class];
$export = [];
foreach ($sources as $source) {
    $rows = $source::manifest();
    if (count($rows) !== count($source::KEYS) * 5 || array_filter($rows, fn (array $row): bool => count($row['samples']) !== count($source::sounds())) !== []) {
        throw new RuntimeException('Every Phrygian scale must have complete key and instrument coverage.');
    }
    $export[$source::TITLE] = $rows;
}
DB::transaction(function () use ($sources, $export): void {
    $existing = Quiz::where('category', PhrygianScaleSamples::CATEGORY)->whereIn('title', [PhrygianScaleSamples::TITLE, PhrygianScaleSamples::LEGACY_TITLE, PhrygianScaleAllKeysSamples::TITLE])->get();
    if (! app()->runningUnitTests() && $existing->isNotEmpty()) {
        $questionIds = $existing->flatMap(fn ($quiz) => $quiz->questions()->pluck('id'));
        $attempts = $existing->flatMap(fn ($quiz) => $quiz->attempts()->get());
        $backup = ['quizzes' => $existing->map->getAttributes()->all(), 'questions' => $existing->flatMap(fn ($quiz) => $quiz->questions()->get()->map->getAttributes())->all(), 'audio' => QuizQuestionAudio::whereIn('quiz_question_id', $questionIds)->get()->map->getAttributes()->all(), 'attempts' => $attempts->map->getAttributes()->all(), 'answers' => DB::table('quiz_attempt_answers')->whereIn('quiz_attempt_id', $attempts->pluck('id'))->get()->all()];
        $path = storage_path('app/private/phrygian-scales-before-live-mix-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json');
        if (file_put_contents($path, json_encode($backup, JSON_THROW_ON_ERROR)) === false) {
            throw new RuntimeException('Could not save Phrygian Scales backup.');
        }
        echo 'Saved backup: '.$path.PHP_EOL;
    }
    foreach ($sources as $source) {
        $quiz = Quiz::where('category', $source::CATEGORY)->where('title', $source::TITLE)->first();
        if ($quiz === null && $source === PhrygianScaleSamples::class) {
            $quiz = Quiz::where('category', $source::CATEGORY)->where('title', PhrygianScaleSamples::LEGACY_TITLE)->first();
        }
        $quiz ??= new Quiz(['category' => $source::CATEGORY, 'slug' => Quiz::uniqueSlug($source::TITLE)]);
        $keyDescription = $source === PhrygianScaleSamples::class ? 'C only' : 'all twelve keys';
        $quiz->fill([
            'title' => $source::TITLE,
            'difficulty' => 'Intermediate',
            'question_count' => 25,
            'is_published' => true,
            'description' => 'Identify '.implode(', ', $source::choices()).' in '.$keyDescription.'. Each scale ascends and descends one octave at the same pace, using the five Phrygian variants from Modal Voicings.',
            'guide_text' => 'Listen to the scale played up and back down one octave in '.$keyDescription.'. Choose the Phrygian variant you hear. All five retain the flat second and perfect fifth; compare the third, fourth, sixth, and seventh degrees.',
        ])->save();
        $quiz->attempts()->delete();
        $quiz->questions()->delete();
        foreach ($export[$source::TITLE] as $index => $row) {
            $question = $quiz->questions()->create(['position' => $index + 1, 'prompt' => 'Listen to the scale and identify the Phrygian variant.', 'choices' => $source::choices(), 'answer' => $row['answer']]);
            foreach ($row['samples'] as $slug => $notes) {
                $question->audioVariants()->create(['sound' => array_search($slug, QuizCatalog::SOUND_SLUGS, true), 'audio_path' => $source::PREFIX.'/'.$slug.'/'.$row['recipe'].'.wav']);
            }
        }
    }
});
if (! app()->runningUnitTests()) {
    if (file_put_contents(storage_path('app/private/phrygian-scales.json'), json_encode($export, JSON_THROW_ON_ERROR)) === false) {
        throw new RuntimeException('Could not export Phrygian Scales recipes.');
    }
}
echo 'Built two Phrygian Scales quizzes with '.array_sum(array_map('count', $export)).' examples and '.array_sum(array_map(fn (array $rows): int => array_sum(array_map(fn (array $row): int => count($row['samples']), $rows)), $export)).' instrument mixes.'.PHP_EOL;
