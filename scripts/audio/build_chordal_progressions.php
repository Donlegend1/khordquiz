<?php

use App\Models\Quiz;
use App\Models\QuizQuestionAudio;
use App\Support\ChordalProgressionSamples;
use Illuminate\Support\Facades\DB;

config(['cache.default' => 'array']);
$sources = Quiz::whereIn('title', ['Chordal melodies', 'Chordal Bass Lines'])->with('questions.audioVariants')->get();
if ($sources->count() !== 2) {
    throw new RuntimeException('Both chordal source quizzes are required.');
}
$manifest = [];
foreach ($sources as $source) {
    foreach ($source->questions->sortBy('position') as $question) {
        $row = ChordalProgressionSamples::fromSource($question);
        if ($row !== null) {
            foreach (ChordalProgressionSamples::inAllKeys($row) as $transposed) {
                $manifest[$transposed['recipe']] = $transposed;
            }
        }
    }
}
if ($manifest === []) {
    throw new RuntimeException('No five-chord source sequences found.');
}
DB::transaction(function () use ($manifest): void {
    $quiz = Quiz::where('title', 'Chordal Progressions')->firstOrFail();
    if (! app()->runningUnitTests()) {
        $backup = [
            'quiz' => $quiz->getAttributes(),
            'questions' => $quiz->questions()->get()->map->getAttributes()->all(),
            'audio' => QuizQuestionAudio::whereIn('quiz_question_id', $quiz->questions()->select('id'))->get()->map->getAttributes()->all(),
            'attempts' => $quiz->attempts()->get()->map->getAttributes()->all(),
            'answers' => DB::table('quiz_attempt_answers')->whereIn('quiz_attempt_id', $quiz->attempts()->select('id'))->get()->all(),
        ];
        $path = storage_path('app/private/chordal-progressions-before-live-mix-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json');
        if (file_put_contents($path, json_encode($backup, JSON_THROW_ON_ERROR)) === false) {
            throw new RuntimeException('Could not save chordal progression backup.');
        }
        echo 'Saved backup: '.$path.PHP_EOL;
    }
    $quiz->attempts()->delete();
    $quiz->questions()->delete();
    foreach (array_values($manifest) as $index => $row) {
        $question = $quiz->questions()->create([
            'position' => $index + 1,
            'prompt' => 'Identify the quality of the five chords in the order you hear them.',
            'choices' => [], 'answer' => $row['answer'],
        ]);
        foreach ($row['audio'] as $sound => $path) {
            $question->audioVariants()->create(['sound' => $sound, 'audio_path' => $path]);
        }
    }
    $quiz->update(['question_count' => 25, 'description' => 'Identify five chord qualities in order in all 12 keys. Original and drop-2 sequences are transposed as a whole into each instrument’s comfortable register; sequences that cannot fit are excluded.']);
});
$mobile = array_map(fn (array $row): array => array_diff_key($row, ['audio' => true]), array_values($manifest));
file_put_contents(storage_path('app/private/chordal-progressions.json'), json_encode($mobile, JSON_THROW_ON_ERROR));
echo 'Created '.count($manifest).' five-chord questions and '.array_sum(array_map(fn (array $row): int => count($row['audio']), $manifest)).' instrument mixes.'.PHP_EOL;
