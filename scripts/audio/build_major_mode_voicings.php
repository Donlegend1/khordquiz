<?php

use App\Models\Quiz;
use App\Models\QuizQuestionAudio;
use App\Support\MajorModeVoicingSamples;
use App\Support\QuizCatalog;
use Illuminate\Support\Facades\DB;

if (PHP_SAPI === 'cli' && ! app()->runningUnitTests()) {
    ini_set('memory_limit', '512M');
}

config(['cache.default' => 'array']);
$title = MajorModeVoicingSamples::TITLE;
$prefix = MajorModeVoicingSamples::PREFIX;
$exportName = 'modal-voicings-major-modes';
$prompt = 'Listen to the voicing and identify the mode.';
$description = 'Compare Romanian Major, Hungarian Major, Byzantine Major, Harmonic Major, Melodic Major, and Major Augmented. Each seven-note chord uses C in the bass and the agreed six-note upper structure, with a common bass octave per instrument. Byzantine Major, Gypsy Major, and Double Harmonic Major are one answer option.';
$manifest = MajorModeVoicingSamples::manifest();
if (count($manifest) !== 6) {
    throw new RuntimeException('All six major-mode voicings must have recorded instrument versions in C.');
}
DB::transaction(function () use ($manifest, $title, $prefix, $prompt, $description, $exportName): void {
    $quiz = Quiz::firstOrCreate(
        ['category' => MajorModeVoicingSamples::CATEGORY, 'title' => $title],
        ['slug' => 'modal-voicings-major-modes', 'difficulty' => 'Advanced', 'question_count' => 25, 'description' => $description, 'is_published' => true],
    );
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
            throw new RuntimeException('Could not save modal voicing backup.');
        }
        echo 'Saved backup: '.$path.PHP_EOL;
    }
    $quiz->attempts()->delete();
    $quiz->questions()->delete();
    foreach (array_values($manifest) as $index => $row) {
        $question = $quiz->questions()->create([
            'position' => $index + 1,
            'prompt' => $prompt,
            'choices' => MajorModeVoicingSamples::choices(), 'answer' => $row['answer'],
        ]);
        foreach ($row['samples'] as $slug => $notes) {
            $question->audioVariants()->create(['sound' => array_search($slug, QuizCatalog::SOUND_SLUGS, true), 'audio_path' => $prefix.'/'.$slug.'/'.$row['recipe'].'.wav']);
        }
    }
    $quiz->update(['title' => $title, 'question_count' => 25, 'description' => $description, 'is_published' => true]);
});
if (! app()->runningUnitTests()) {
    file_put_contents(storage_path('app/private/'.$exportName.'.json'), json_encode($manifest, JSON_THROW_ON_ERROR));
}
echo 'Created '.count($manifest).' modal voicings and '.array_sum(array_map(fn (array $row): int => count($row['samples']), $manifest)).' instrument mixes.'.PHP_EOL;
