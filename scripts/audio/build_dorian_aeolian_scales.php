<?php

use App\Models\Quiz;
use App\Models\QuizQuestionAudio;
use App\Support\DorianAeolianScaleSamples;
use App\Support\QuizCatalog;
use Illuminate\Support\Facades\DB;

config(['cache.default' => 'array']);
$manifest = DorianAeolianScaleSamples::manifest();
if (count($manifest) !== 24 || array_filter($manifest, fn (array $row): bool => count($row['samples']) !== count(DorianAeolianScaleSamples::sounds())) !== []) {
    throw new RuntimeException('Both scales in all twelve keys must have complete instrument versions.');
}
DB::transaction(function () use ($manifest): void {
    $quiz = Quiz::firstOrCreate(
        ['category' => DorianAeolianScaleSamples::CATEGORY, 'title' => DorianAeolianScaleSamples::TITLE],
        ['slug' => Quiz::uniqueSlug(DorianAeolianScaleSamples::TITLE), 'difficulty' => 'Intermediate', 'question_count' => 25, 'is_published' => true],
    );
    if (! $quiz->wasRecentlyCreated && ! app()->runningUnitTests()) {
        $backup = [
            'quiz' => $quiz->getAttributes(),
            'questions' => $quiz->questions()->get()->map->getAttributes()->all(),
            'audio' => QuizQuestionAudio::whereIn('quiz_question_id', $quiz->questions()->select('id'))->get()->map->getAttributes()->all(),
            'attempts' => $quiz->attempts()->get()->map->getAttributes()->all(),
            'answers' => DB::table('quiz_attempt_answers')->whereIn('quiz_attempt_id', $quiz->attempts()->select('id'))->get()->all(),
        ];
        $path = storage_path('app/private/dorian-aeolian-scales-before-live-mix-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json');
        if (file_put_contents($path, json_encode($backup, JSON_THROW_ON_ERROR)) === false) {
            throw new RuntimeException('Could not save Dorian and Aeolian backup.');
        }
        echo 'Saved backup: '.$path.PHP_EOL;
    }
    $quiz->attempts()->delete();
    $quiz->questions()->delete();
    foreach ($manifest as $index => $row) {
        $question = $quiz->questions()->create(['position' => $index + 1, 'prompt' => 'Listen to the scale and choose Dorian or Aeolian.', 'choices' => DorianAeolianScaleSamples::choices(), 'answer' => $row['answer']]);
        foreach ($row['samples'] as $slug => $notes) {
            $question->audioVariants()->create(['sound' => array_search($slug, QuizCatalog::SOUND_SLUGS, true), 'audio_path' => DorianAeolianScaleSamples::PREFIX.'/'.$slug.'/'.$row['recipe'].'.wav']);
        }
    }
    $quiz->update([
        'difficulty' => 'Intermediate',
        'question_count' => 25,
        'is_published' => true,
        'description' => 'Compare Dorian and Aeolian across all twelve keys. Each scale ascends and descends one octave at the same pace and uses a comfortable register for the instrument.',
        'guide_text' => 'Listen to the scale played up and back down one octave. The examples cover all twelve keys. Choose Dorian or Aeolian (natural minor). Dorian has a natural sixth; Aeolian has a lowered sixth.',
    ]);
});
if (! app()->runningUnitTests()) {
    if (file_put_contents(storage_path('app/private/dorian-aeolian-scales.json'), json_encode($manifest, JSON_THROW_ON_ERROR)) === false) {
        throw new RuntimeException('Could not export Dorian and Aeolian audio recipes.');
    }
}
echo 'Built 24 Dorian and Aeolian scales across all twelve keys with '.array_sum(array_map(fn (array $row): int => count($row['samples']), $manifest)).' instrument mixes.'.PHP_EOL;
