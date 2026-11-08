<?php

use App\Models\Quiz;
use App\Models\QuizQuestionAudio;
use App\Support\QuizCatalog;
use App\Support\TonalModeScaleSamples;
use Illuminate\Support\Facades\DB;

config(['cache.default' => 'array']);
$manifest = TonalModeScaleSamples::manifest();
if (count($manifest) !== 5 || array_filter($manifest, fn (array $row): bool => count($row['samples']) !== count(TonalModeScaleSamples::sounds())) !== []) {
    throw new RuntimeException('All five F tonal scales must have complete instrument versions.');
}
DB::transaction(function () use ($manifest): void {
    $quiz = Quiz::where('category', TonalModeScaleSamples::CATEGORY)->whereIn('title', [TonalModeScaleSamples::TITLE, TonalModeScaleSamples::PREVIOUS_TITLE])->first();
    if ($quiz === null) {
        $quiz = Quiz::create(['category' => TonalModeScaleSamples::CATEGORY, 'title' => TonalModeScaleSamples::TITLE, 'slug' => Quiz::uniqueSlug(TonalModeScaleSamples::TITLE), 'difficulty' => 'Intermediate', 'question_count' => 25, 'is_published' => true]);
    }
    if (! $quiz->wasRecentlyCreated && ! app()->runningUnitTests()) {
        $backup = [
            'quiz' => $quiz->getAttributes(),
            'questions' => $quiz->questions()->get()->map->getAttributes()->all(),
            'audio' => QuizQuestionAudio::whereIn('quiz_question_id', $quiz->questions()->select('id'))->get()->map->getAttributes()->all(),
            'attempts' => $quiz->attempts()->get()->map->getAttributes()->all(),
            'answers' => DB::table('quiz_attempt_answers')->whereIn('quiz_attempt_id', $quiz->attempts()->select('id'))->get()->all(),
        ];
        $path = storage_path('app/private/tonal-mode-scales-before-live-mix-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json');
        if (file_put_contents($path, json_encode($backup, JSON_THROW_ON_ERROR)) === false) {
            throw new RuntimeException('Could not save Tonal Modes backup.');
        }
        echo 'Saved backup: '.$path.PHP_EOL;
    }
    $quiz->attempts()->delete();
    $quiz->questions()->delete();
    foreach ($manifest as $index => $row) {
        $question = $quiz->questions()->create(['position' => $index + 1, 'prompt' => 'Listen to the scale in F and identify the tonal mode.', 'choices' => TonalModeScaleSamples::choices(), 'answer' => $row['answer']]);
        foreach ($row['samples'] as $slug => $notes) {
            $question->audioVariants()->create(['sound' => array_search($slug, QuizCatalog::SOUND_SLUGS, true), 'audio_path' => TonalModeScaleSamples::PREFIX.'/'.$slug.'/'.$row['recipe'].'.wav']);
        }
    }
    $quiz->update([
        'title' => TonalModeScaleSamples::TITLE,
        'difficulty' => 'Intermediate',
        'question_count' => 25,
        'is_published' => true,
        'description' => 'Identify Ionian, Harmonic Major, Harmonic Minor, Melodic Minor, and Aeolian in F only. Each scale ascends and descends one octave at the same pace. Melodic Minor uses the ascending/jazz form in both directions.',
        'guide_text' => 'Listen to the scale played one note at a time, up and back down one octave. Every example starts and ends on F. Choose the tonal mode you hear. Melodic Minor keeps its raised sixth and seventh in both directions.',
    ]);
});
if (! app()->runningUnitTests()) {
    if (file_put_contents(storage_path('app/private/tonal-mode-scales.json'), json_encode($manifest, JSON_THROW_ON_ERROR)) === false) {
        throw new RuntimeException('Could not export Tonal Modes audio recipes.');
    }
}
echo 'Built five tonal scales in F with '.array_sum(array_map(fn (array $row): int => count($row['samples']), $manifest)).' instrument mixes.'.PHP_EOL;
