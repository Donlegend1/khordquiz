<?php

use App\Models\Quiz;
use App\Models\QuizQuestionAudio;
use App\Support\PhrygianVoicingSamples;
use App\Support\QuizCatalog;
use Illuminate\Support\Facades\DB;

if (PHP_SAPI === 'cli' && ! app()->runningUnitTests()) {
    ini_set('memory_limit', '512M');
}

config(['cache.default' => 'array']);
$title = PhrygianVoicingSamples::TITLE;
$prefix = PhrygianVoicingSamples::PREFIX;
$exportName = 'modal-voicings-phrygian';
$prompt = 'Listen to the voicing and identify the mode.';
$description = 'Compare Phrygian, Phrygian Dominant, Phrygian (♭4), Phrygian ♮6, and Phrygian ♮7 over C. Each fixed six-note voicing changes one note from C–E♭–A♭–B♭–D♭–F while preserving the same C bass octave and other note positions. Phrygian (♭4) uses 1–♭2–♭3–♭4–5–♭6–♭7.';
$manifest = PhrygianVoicingSamples::manifest();
if (count($manifest) !== 5) {
    throw new RuntimeException('All five Phrygian-family voicings must have recorded instrument versions in C.');
}
DB::transaction(function () use ($manifest, $title, $prefix, $prompt, $description, $exportName): void {
    $quiz = Quiz::where('category', PhrygianVoicingSamples::CATEGORY)->whereIn('title', [$title, PhrygianVoicingSamples::LEGACY_TITLE])->firstOrFail();
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
            'choices' => PhrygianVoicingSamples::choices(), 'answer' => $row['answer'],
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
