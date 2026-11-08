<?php

use App\Models\Quiz;
use App\Models\QuizQuestionAudio;
use App\Support\QuizCatalog;
use App\Support\TonalModeAllKeysSamples;
use App\Support\TonalModeScaleSamples;
use Illuminate\Support\Facades\DB;

config(['cache.default' => 'array']);
$manifest = TonalModeAllKeysSamples::manifest();
if (count($manifest) !== 60 || array_filter($manifest, fn (array $row): bool => count($row['samples']) !== count(TonalModeAllKeysSamples::sounds())) !== []) {
    throw new RuntimeException('All five tonal scales in all twelve keys must have complete instrument versions.');
}
DB::transaction(function () use ($manifest): void {
    $fOnly = Quiz::where('category', TonalModeScaleSamples::CATEGORY)
        ->whereIn('title', [TonalModeScaleSamples::TITLE, TonalModeScaleSamples::PREVIOUS_TITLE])->first();
    if ($fOnly !== null && $fOnly->title !== TonalModeScaleSamples::TITLE) {
        if (! app()->runningUnitTests()) {
            $path = storage_path('app/private/tonal-modes-before-numbering-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json');
            if (file_put_contents($path, json_encode($fOnly->getAttributes(), JSON_THROW_ON_ERROR)) === false) {
                throw new RuntimeException('Could not back up the F-only quiz before renaming.');
            }
            echo 'Saved rename backup: '.$path.PHP_EOL;
        }
        $fOnly->update(['title' => TonalModeScaleSamples::TITLE]);
    }

    $quiz = Quiz::firstOrCreate(
        ['category' => TonalModeAllKeysSamples::CATEGORY, 'title' => TonalModeAllKeysSamples::TITLE],
        ['slug' => Quiz::uniqueSlug(TonalModeAllKeysSamples::TITLE), 'difficulty' => 'Intermediate', 'question_count' => 25, 'is_published' => true],
    );
    if (! $quiz->wasRecentlyCreated && ! app()->runningUnitTests()) {
        $backup = [
            'quiz' => $quiz->getAttributes(),
            'questions' => $quiz->questions()->get()->map->getAttributes()->all(),
            'audio' => QuizQuestionAudio::whereIn('quiz_question_id', $quiz->questions()->select('id'))->get()->map->getAttributes()->all(),
            'attempts' => $quiz->attempts()->get()->map->getAttributes()->all(),
            'answers' => DB::table('quiz_attempt_answers')->whereIn('quiz_attempt_id', $quiz->attempts()->select('id'))->get()->all(),
        ];
        $path = storage_path('app/private/tonal-mode-scales-two-before-live-mix-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json');
        if (file_put_contents($path, json_encode($backup, JSON_THROW_ON_ERROR)) === false) {
            throw new RuntimeException('Could not save Tonal Modes #2 backup.');
        }
        echo 'Saved backup: '.$path.PHP_EOL;
    }
    $quiz->attempts()->delete();
    $quiz->questions()->delete();
    foreach ($manifest as $index => $row) {
        $question = $quiz->questions()->create(['position' => $index + 1, 'prompt' => 'Listen to the scale and identify the mode.', 'choices' => TonalModeAllKeysSamples::choices(), 'answer' => $row['answer']]);
        foreach ($row['samples'] as $slug => $notes) {
            $question->audioVariants()->create(['sound' => array_search($slug, QuizCatalog::SOUND_SLUGS, true), 'audio_path' => TonalModeAllKeysSamples::PREFIX.'/'.$slug.'/'.$row['recipe'].'.wav']);
        }
    }
    $quiz->update([
        'difficulty' => 'Intermediate',
        'question_count' => 25,
        'is_published' => true,
        'description' => 'Identify Ionian, Harmonic Major, Harmonic Minor, Melodic Minor, and Aeolian across all twelve keys. Each scale ascends and descends one octave at the same pace and uses a comfortable register for the instrument.',
        'guide_text' => 'Listen to the scale played up and back down one octave in any of the twelve keys. Choose Ionian, Harmonic Major, Harmonic Minor, Melodic Minor, or Aeolian. Melodic Minor uses its ascending/jazz form in both directions.',
    ]);
});
if (! app()->runningUnitTests()) {
    if (file_put_contents(storage_path('app/private/tonal-mode-scales-two.json'), json_encode($manifest, JSON_THROW_ON_ERROR)) === false) {
        throw new RuntimeException('Could not export Tonal Modes #2 audio recipes.');
    }
}
echo 'Built 60 tonal scales across all twelve keys with '.array_sum(array_map(fn (array $row): int => count($row['samples']), $manifest)).' instrument mixes.'.PHP_EOL;
