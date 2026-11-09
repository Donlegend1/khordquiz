<?php

use App\Models\Quiz;
use App\Models\QuizQuestionAudio;
use App\Support\MajorScaleAllKeysSamples;
use App\Support\MajorScaleSamples;
use App\Support\QuizCatalog;
use Illuminate\Support\Facades\DB;

config(['cache.default' => 'array']);
$manifest = MajorScaleAllKeysSamples::manifest();
if (count($manifest) !== 84 || array_filter($manifest, fn (array $row): bool => count($row['samples']) !== count(MajorScaleAllKeysSamples::sounds())) !== []) {
    throw new RuntimeException('All seven modes in all twelve keys must have complete instrument versions.');
}
DB::transaction(function () use ($manifest): void {
    $cOnly = Quiz::where('category', MajorScaleSamples::CATEGORY)
        ->whereIn('title', [MajorScaleSamples::TITLE, MajorScaleSamples::PREVIOUS_TITLE, MajorScaleSamples::LEGACY_TITLE])->first();
    if ($cOnly !== null && $cOnly->title !== MajorScaleSamples::TITLE) {
        if (! app()->runningUnitTests()) {
            $path = storage_path('app/private/major-scales-before-numbering-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json');
            if (file_put_contents($path, json_encode($cOnly->getAttributes(), JSON_THROW_ON_ERROR)) === false) {
                throw new RuntimeException('Could not back up the C-only quiz before renaming.');
            }
            echo 'Saved rename backup: '.$path.PHP_EOL;
        }
        $cOnly->update(['title' => MajorScaleSamples::TITLE]);
    }

    $quiz = Quiz::firstOrCreate(
        ['category' => MajorScaleAllKeysSamples::CATEGORY, 'title' => MajorScaleAllKeysSamples::TITLE],
        ['slug' => Quiz::uniqueSlug(MajorScaleAllKeysSamples::TITLE), 'difficulty' => 'Intermediate', 'question_count' => 25, 'is_published' => true],
    );
    if (! $quiz->wasRecentlyCreated && ! app()->runningUnitTests()) {
        $backup = [
            'quiz' => $quiz->getAttributes(),
            'questions' => $quiz->questions()->get()->map->getAttributes()->all(),
            'audio' => QuizQuestionAudio::whereIn('quiz_question_id', $quiz->questions()->select('id'))->get()->map->getAttributes()->all(),
            'attempts' => $quiz->attempts()->get()->map->getAttributes()->all(),
            'answers' => DB::table('quiz_attempt_answers')->whereIn('quiz_attempt_id', $quiz->attempts()->select('id'))->get()->all(),
        ];
        $path = storage_path('app/private/major-scales-two-before-live-mix-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json');
        if (file_put_contents($path, json_encode($backup, JSON_THROW_ON_ERROR)) === false) {
            throw new RuntimeException('Could not save Major Scales #2 backup.');
        }
        echo 'Saved backup: '.$path.PHP_EOL;
    }
    $quiz->attempts()->delete();
    $quiz->questions()->delete();
    foreach ($manifest as $index => $row) {
        $question = $quiz->questions()->create(['position' => $index + 1, 'prompt' => 'Listen to the scale and identify the mode.', 'choices' => MajorScaleAllKeysSamples::choices(), 'answer' => $row['answer']]);
        foreach ($row['samples'] as $slug => $notes) {
            $question->audioVariants()->create(['sound' => array_search($slug, QuizCatalog::SOUND_SLUGS, true), 'audio_path' => MajorScaleAllKeysSamples::PREFIX.'/'.$slug.'/'.$row['recipe'].'.wav']);
        }
    }
    $quiz->update([
        'difficulty' => 'Intermediate',
        'question_count' => 25,
        'is_published' => true,
        'description' => 'Identify Ionian, Dorian, Phrygian, Lydian, Mixolydian, Aeolian, and Locrian across all twelve keys. Each scale ascends and descends one octave at the same pace and uses a comfortable register for the instrument.',
        'guide_text' => 'Listen to the mode played up and back down one octave. The examples cover all twelve keys. Identify one of the seven modes of the major scale relative to the tonic you hear.',
    ]);
});
if (! app()->runningUnitTests()) {
    if (file_put_contents(storage_path('app/private/major-scales-two.json'), json_encode($manifest, JSON_THROW_ON_ERROR)) === false) {
        throw new RuntimeException('Could not export Major Scales #2 audio recipes.');
    }
}
echo 'Built 84 major-scale modes across all twelve keys with '.array_sum(array_map(fn (array $row): int => count($row['samples']), $manifest)).' instrument mixes.'.PHP_EOL;
