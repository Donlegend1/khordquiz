<?php

use App\Models\Quiz;
use App\Models\QuizQuestionAudio;
use App\Support\MajorScaleSamples;
use App\Support\QuizCatalog;
use Illuminate\Support\Facades\DB;

config(['cache.default' => 'array']);
$manifest = MajorScaleSamples::manifest();
if (count($manifest) !== 7 || array_filter($manifest, fn (array $row): bool => count($row['samples']) !== count(MajorScaleSamples::sounds())) !== []) {
    throw new RuntimeException('All seven modes in C must have complete instrument versions.');
}
DB::transaction(function () use ($manifest): void {
    $quiz = Quiz::where('category', MajorScaleSamples::CATEGORY)->where('title', MajorScaleSamples::TITLE)->first()
        ?? Quiz::where('category', MajorScaleSamples::CATEGORY)->whereIn('title', [MajorScaleSamples::PREVIOUS_TITLE, MajorScaleSamples::LEGACY_TITLE])->first();
    if ($quiz === null) {
        $quiz = Quiz::create(['category' => MajorScaleSamples::CATEGORY, 'title' => MajorScaleSamples::TITLE, 'slug' => Quiz::uniqueSlug(MajorScaleSamples::TITLE), 'difficulty' => 'Intermediate', 'question_count' => 25, 'is_published' => true]);
    }
    if (! $quiz->wasRecentlyCreated && ! app()->runningUnitTests()) {
        $backup = [
            'quiz' => $quiz->getAttributes(),
            'questions' => $quiz->questions()->get()->map->getAttributes()->all(),
            'audio' => QuizQuestionAudio::whereIn('quiz_question_id', $quiz->questions()->select('id'))->get()->map->getAttributes()->all(),
            'attempts' => $quiz->attempts()->get()->map->getAttributes()->all(),
            'answers' => DB::table('quiz_attempt_answers')->whereIn('quiz_attempt_id', $quiz->attempts()->select('id'))->get()->all(),
        ];
        $path = storage_path('app/private/major-scales-before-live-mix-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json');
        if (file_put_contents($path, json_encode($backup, JSON_THROW_ON_ERROR)) === false) {
            throw new RuntimeException('Could not save Major Scales backup.');
        }
        echo 'Saved backup: '.$path.PHP_EOL;
    }
    $quiz->attempts()->delete();
    $quiz->questions()->delete();
    foreach ($manifest as $index => $row) {
        $question = $quiz->questions()->create(['position' => $index + 1, 'prompt' => 'Listen to the scale in C and identify the mode.', 'choices' => MajorScaleSamples::choices(), 'answer' => $row['answer']]);
        foreach ($row['samples'] as $slug => $notes) {
            $question->audioVariants()->create(['sound' => array_search($slug, QuizCatalog::SOUND_SLUGS, true), 'audio_path' => MajorScaleSamples::PREFIX.'/'.$slug.'/'.$row['recipe'].'.wav']);
        }
    }
    $quiz->update([
        'title' => MajorScaleSamples::TITLE,
        'difficulty' => 'Intermediate',
        'question_count' => 25,
        'is_published' => true,
        'description' => 'Identify Ionian, Dorian, Phrygian, Lydian, Mixolydian, Aeolian, and Locrian. Every mode starts on C and moves up and down one octave at the same pace.',
        'guide_text' => 'Listen to the mode played up and back down one octave. Every example starts and ends on C. Choose one of the seven modes of the major scale. Compare the intervals relative to C.',
    ]);
});
if (! app()->runningUnitTests()) {
    if (file_put_contents(storage_path('app/private/major-scales.json'), json_encode($manifest, JSON_THROW_ON_ERROR)) === false) {
        throw new RuntimeException('Could not export Major Scales audio recipes.');
    }
}
echo 'Built seven modes in C with '.array_sum(array_map(fn (array $row): int => count($row['samples']), $manifest)).' instrument mixes.'.PHP_EOL;
