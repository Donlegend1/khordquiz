<?php

use App\Models\Quiz;
use App\Models\QuizQuestionAudio;
use App\Support\ProgressionRecognitionInversionSamples;
use App\Support\ProgressionRecognitionSamples;
use App\Support\QuizCatalog;
use Illuminate\Support\Facades\DB;

config(['cache.default' => 'array']);
$part = $recognitionPart ?? 1;
$title = match ($part) {
    4 => ProgressionRecognitionInversionSamples::PART_FOUR_TITLE, 3 => ProgressionRecognitionInversionSamples::TITLE, 2 => ProgressionRecognitionSamples::PART_TWO_TITLE, default => ProgressionRecognitionSamples::TITLE
};
$prefix = match ($part) {
    4 => ProgressionRecognitionInversionSamples::PART_FOUR_PREFIX, 3 => ProgressionRecognitionInversionSamples::PREFIX, 2 => ProgressionRecognitionSamples::PART_TWO_PREFIX, default => ProgressionRecognitionSamples::PREFIX
};
$exportName = match ($part) {
    4 => 'progression-recognition-part-four', 3 => 'progression-recognition-part-three', 2 => 'progression-recognition-part-two', default => 'progression-recognition-part-one'
};
$prompt = ($part === 2 ? 'In the key you hear' : 'In F').', identify the qualities of 2–5–1. The 2 and 1 are in root position; the 5 is in second inversion.';
$description = $part === 2 ? '#2: all 60 approved 2–5–1 combinations in all 12 keys. The 2 and 1 are in root position; the 5 is in second inversion. Four-note chords stay close together in each instrument’s comfortable register.' : '#1: all 60 approved 2–5–1 combinations in F. The G chord is in root position, the C chord is in second inversion, and the F chord is in root position. Four-note chords are played close together in each instrument’s comfortable register.';
if ($part === 3 || $part === 4) {
    $prompt = ($part === 4 ? 'In the key you hear' : 'In F').', identify the qualities of 2–5–1. The 5 and 1 use their closest inversions to the preceding chord.';
    $description = ($part === 4 ? '#4: the same 60 approved 2–5–1 quality combinations in all 12 keys.' : '#3: the same 60 approved 2–5–1 quality combinations in F.').' Start the 2 chord in any of its four positions, then use the nearest inversion of 5 followed by the nearest inversion of 1. Each transition minimizes total movement across the four voices within the instrument’s comfortable register.';
}
$manifest = $part >= 3 ? ProgressionRecognitionInversionSamples::manifest($part) : ProgressionRecognitionSamples::manifest($part);
if (count($manifest) !== match ($part) {
    4 => 2880, 3 => 240, 2 => 720, default => 60
}) {
    throw new RuntimeException('Every approved key and quality combination must have playable audio.');
}
DB::transaction(function () use ($manifest, $part, $title, $prefix, $prompt, $description, $exportName): void {
    $quiz = $part === 1 ? Quiz::whereIn('title', [$title, 'Progression recognition'])->firstOrFail() : Quiz::firstOrNew(['title' => $title]);
    if (! $quiz->exists) {
        $quiz->fill(['slug' => Quiz::uniqueSlug($title), 'category' => 'Chord Progressions', 'difficulty' => 'Intermediate', 'question_count' => 25, 'is_published' => true, 'created_by' => Quiz::where('title', ProgressionRecognitionSamples::TITLE)->value('created_by')])->save();
    }
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
            throw new RuntimeException('Could not save progression recognition backup.');
        }
        echo 'Saved backup: '.$path.PHP_EOL;
    }
    $quiz->attempts()->delete();
    $quiz->questions()->delete();
    foreach (array_values($manifest) as $index => $row) {
        $question = $quiz->questions()->create([
            'position' => $index + 1,
            'prompt' => $prompt,
            'choices' => [], 'answer' => $row['answer'],
        ]);
        foreach ($row['samples'] as $slug => $notes) {
            $question->audioVariants()->create(['sound' => array_search($slug, QuizCatalog::SOUND_SLUGS, true), 'audio_path' => $prefix.'/'.$slug.'/'.$row['recipe'].'.wav']);
        }
    }
    $quiz->update(['title' => $title, 'question_count' => 25, 'description' => $description]);
});
if (! app()->runningUnitTests()) {
    file_put_contents(storage_path('app/private/'.$exportName.'.json'), json_encode($part === 2 ? ProgressionRecognitionSamples::mobileManifest($manifest) : $manifest, JSON_THROW_ON_ERROR));
}
echo 'Created '.count($manifest).' key/quality sequences and '.array_sum(array_map(fn (array $row): int => count($row['samples']), $manifest)).' instrument mixes.'.PHP_EOL;
