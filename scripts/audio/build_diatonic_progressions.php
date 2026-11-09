<?php

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;
use App\Support\DiatonicProgressionSamples;
use App\Support\QuizCatalog;
use Illuminate\Support\Facades\DB;

config(['cache.default' => 'array']);
$sampleClass = $progressionSampleClass ?? DiatonicProgressionSamples::class;
$title = $progressionTitle ?? 'Diatonic Progression #1';
$prefix = $progressionPrefix ?? 'diatonic-progressions/all-keys-v1';
$exportName = $progressionExportName ?? 'diatonic-progressions';
$manifest = $sampleClass::manifest();
DB::transaction(function () use ($manifest, $title, $prefix, $exportName): void {
    $quiz = Quiz::firstOrCreate(['title' => $title], [
        'slug' => Quiz::uniqueSlug($title), 'category' => 'Chord Progressions', 'difficulty' => 'Intermediate',
        'question_count' => 25, 'is_published' => true,
    ]);
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
            throw new RuntimeException('Could not save diatonic progression backup.');
        }
        echo 'Saved backup: '.$path.PHP_EOL;
    }
    $quiz->update(['category' => 'Chord Progressions', 'question_count' => 25,
        'description' => 'A root-position tonic triad and tonic note introduce four diatonic chords in any of the 12 major keys. Each progression chord has four voices, with close movement and an instrument-appropriate register.',
    ]);
    $existing = $quiz->questions()->with('audioVariants')->get();
    $byRecipe = $existing->keyBy(fn (QuizQuestion $question): string => basename($question->audioVariants->first()?->audio_path ?? '', '.wav'));
    $nextPosition = (int) $existing->max('position');
    $kept = [];
    $mixes = 0;
    foreach ($manifest as $row) {
        $question = $byRecipe->get($row['recipe']) ?? new QuizQuestion(['quiz_id' => $quiz->id, 'position' => ++$nextPosition]);
        $question->fill(['prompt' => 'First hear the key’s major triad and tonic note. Then identify the four progression chords.', 'choices' => [], 'answer' => $row['answer']])->save();
        $sounds = [];
        foreach ($row['samples'] as $slug => $notes) {
            $sound = array_search($slug, QuizCatalog::SOUND_SLUGS, true);
            $sounds[] = $sound;
            $question->audioVariants()->updateOrCreate(['sound' => $sound], ['audio_path' => $prefix.'/'.$slug.'/'.$row['recipe'].'.wav']);
            $mixes++;
        }
        $question->audioVariants()->whereNotIn('sound', $sounds)->delete();
        $kept[] = $question->id;
    }
    if ($existing->pluck('id')->diff($kept)->isNotEmpty()) {
        QuizAttempt::where('quiz_id', $quiz->id)->delete();
        $quiz->questions()->whereNotIn('id', $kept)->delete();
    }
    echo $title.': '.count($manifest).' sequence/key/voicing combinations, '.$mixes.' playable instrument mixes.'.PHP_EOL;
});
if (! app()->runningUnitTests()) {
    $json = json_encode($sampleClass::mobileManifest($manifest), JSON_THROW_ON_ERROR);
    if (file_put_contents(storage_path('app/private/'.$exportName.'-mobile.json'), $json) === false) {
        throw new RuntimeException('Could not save mobile diatonic progression data.');
    }
}
