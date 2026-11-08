<?php

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;
use Illuminate\Support\Facades\DB;

$quizzes = Quiz::where('category', 'Major Chord Extensions')
    ->whereIn('title', ['Major Chord Extentions · Level 5', 'Major Chord Extentions · Level 6', 'Major Chord Extentions · Level 7'])
    ->get(['id', 'title']);
$remove = [];
foreach ($quizzes as $quiz) {
    $count = 0;
    foreach ($quiz->questions()->with('audioVariants:id,quiz_question_id,audio_path')->get(['id', 'quiz_id']) as $question) {
        $matches = $question->audioVariants->filter(fn ($audio) => preg_match('#^major-chord-extensions/level3/[a-z-]+/[a-z]+-maj7-v[1-5]\.wav$#', $audio->audio_path) === 1);
        if ($matches->isEmpty()) {
            continue;
        }
        if ($matches->count() !== $question->audioVariants->count()) {
            throw new RuntimeException('Question mixes different chord structures: '.$question->id);
        }
        $remove[] = $question->id;
        $count++;
    }
    echo $quiz->title.': remove '.$count.' Group 3 Major 7 questions.'.PHP_EOL;
}
if (! in_array('--apply', $_SERVER['argv'] ?? [], true)) {
    echo 'Preview only; no database changes.'.PHP_EOL;

    return;
}
if ($remove === []) {
    echo 'No duplicate Group 3 Major 7 questions remain.'.PHP_EOL;

    return;
}
if (! app()->runningUnitTests()) {
    $attempts = QuizAttempt::whereIn('quiz_id', $quizzes->pluck('id'))->get();
    $backup = [
        'quizzes' => Quiz::whereIn('id', $quizzes->pluck('id'))->get()->map->getAttributes()->all(),
        'questions' => QuizQuestion::whereIn('quiz_id', $quizzes->pluck('id'))->get()->map->getAttributes()->all(),
        'audio' => QuizQuestionAudio::whereIn('quiz_question_id', $remove)->get()->map->getAttributes()->all(),
        'attempts' => $attempts->map->getAttributes()->all(),
        'answers' => DB::table('quiz_attempt_answers')->whereIn('quiz_attempt_id', $attempts->pluck('id'))->get()->all(),
    ];
    $backupPath = storage_path('app/private/remove-group3-maj7-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json');
    if (file_put_contents($backupPath, json_encode($backup, JSON_THROW_ON_ERROR)) === false) {
        throw new RuntimeException('Could not save backup.');
    }
    echo 'Saved backup: '.$backupPath.PHP_EOL;
}
DB::transaction(function () use ($quizzes, $remove): void {
    foreach (QuizAttempt::whereIn('quiz_id', $quizzes->pluck('id'))->where('status', QuizAttempt::STATUS_IN_PROGRESS)->get() as $attempt) {
        if (array_intersect($attempt->question_sequence ?? [], $remove) !== [] || $attempt->answers()->whereIn('quiz_question_id', $remove)->exists()) {
            $attempt->delete();
        }
    }
    QuizQuestion::whereIn('id', $remove)->delete();
    foreach ($quizzes as $quiz) {
        foreach ($quiz->questions()->orderBy('position')->get(['id', 'position']) as $index => $question) {
            if ($question->position !== $index + 1) {
                DB::table('quiz_questions')->where('id', $question->id)->update(['position' => $index + 1]);
            }
        }
    }
    echo 'Removed '.count($remove).' Group 3 Major 7 questions; Group 1 and Chord Naming unchanged.'.PHP_EOL;
});
