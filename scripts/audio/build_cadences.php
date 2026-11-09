<?php

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;
use App\Support\CadenceSamples;
use App\Support\QuizCatalog;
use Illuminate\Support\Facades\DB;

config(['cache.default' => 'array']);
$manifest = CadenceSamples::manifest();
DB::transaction(function () use ($manifest): void {
    $quiz = Quiz::firstOrCreate(['title' => 'Cadences'], [
        'slug' => Quiz::uniqueSlug('Cadences'), 'category' => 'Chord Progressions', 'difficulty' => 'Intermediate',
        'description' => 'Identify four cadence families in all 12 major keys with nearby inversions.', 'question_count' => 25, 'is_published' => true,
    ]);
    if (! $quiz->wasRecentlyCreated && ! app()->runningUnitTests()) {
        $backup = [
            'quiz' => $quiz->getAttributes(),
            'questions' => $quiz->questions()->get()->map->getAttributes()->all(),
            'audio' => QuizQuestionAudio::whereIn('quiz_question_id', $quiz->questions()->select('id'))->get()->map->getAttributes()->all(),
            'attempts' => $quiz->attempts()->get()->map->getAttributes()->all(),
            'answers' => DB::table('quiz_attempt_answers')->whereIn('quiz_attempt_id', $quiz->attempts()->select('id'))->get()->all(),
        ];
        $path = storage_path('app/private/cadences-before-live-mix-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json');
        if (file_put_contents($path, json_encode($backup, JSON_THROW_ON_ERROR)) === false) {
            throw new RuntimeException('Could not save cadence backup.');
        }
        echo 'Saved backup: '.$path.PHP_EOL;
    }
    $quiz->update(['category' => 'Chord Progressions', 'question_count' => 25,
        'description' => 'Authentic (V7), Plagal (IV or IVmaj7), Minor Plagal (iv or iv6), and Sub-Plagal (ii or ii7), resolving to I, Iadd9 or Imaj7 in all 12 major keys with close voicings and preferred instrument registers.',
    ]);
    $existing = $quiz->questions()->with('audioVariants')->get();
    $byRecipe = $existing->keyBy(fn (QuizQuestion $question): string => basename($question->audioVariants->first()?->audio_path ?? '', '.wav'));
    $nextPosition = (int) $existing->max('position');
    $kept = [];
    $mixes = 0;
    foreach ($manifest as $row) {
        $question = $byRecipe->get($row['recipe']) ?? new QuizQuestion(['quiz_id' => $quiz->id, 'position' => ++$nextPosition]);
        $question->fill(['prompt' => 'Listen to the progression and identify the cadence.', 'choices' => CadenceSamples::CHOICES, 'answer' => $row['answer']])->save();
        $sounds = [];
        foreach ($row['samples'] as $slug => $notes) {
            $sound = array_search($slug, QuizCatalog::SOUND_SLUGS, true);
            $sounds[] = $sound;
            $question->audioVariants()->updateOrCreate(['sound' => $sound], ['audio_path' => 'cadences/all-keys-v3/'.$slug.'/'.$row['recipe'].'.wav']);
            $mixes++;
        }
        $question->audioVariants()->whereNotIn('sound', $sounds)->delete();
        $kept[] = $question->id;
    }
    if ($existing->pluck('id')->diff($kept)->isNotEmpty()) {
        QuizAttempt::where('quiz_id', $quiz->id)->delete();
        $quiz->questions()->whereNotIn('id', $kept)->delete();
    }
    echo 'Cadences: '.count($manifest).' inversion pairs, '.$mixes.' playable instrument mixes; no Deceptive answers.'.PHP_EOL;
});
if (! app()->runningUnitTests()) {
    if (file_put_contents(storage_path('app/private/cadences-mobile.json'), json_encode($manifest, JSON_THROW_ON_ERROR)) === false) {
        throw new RuntimeException('Could not save mobile cadence data.');
    }
}
