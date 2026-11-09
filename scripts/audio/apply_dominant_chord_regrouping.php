<?php

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;
use App\Support\BasicChordNamingSamples;
use Illuminate\Support\Facades\DB;

config(['cache.default' => 'array']);
$quizzes = Quiz::where('category', 'Dominant Chord Extensions')->get();
$questions = QuizQuestion::whereIn('quiz_id', $quizzes->pluck('id'))->get();
$attempts = QuizAttempt::whereIn('quiz_id', $quizzes->pluck('id'))->get();
$backup = [
    'quizzes' => $quizzes->map->getAttributes()->all(),
    'questions' => $questions->map->getAttributes()->all(),
    'audio' => QuizQuestionAudio::whereIn('quiz_question_id', $questions->pluck('id'))->get()->map->getAttributes()->all(),
    'attempts' => $attempts->map->getAttributes()->all(),
    'answers' => DB::table('quiz_attempt_answers')->whereIn('quiz_attempt_id', $attempts->pluck('id'))->get()->all(),
    'marks' => DB::table('quiz_marks')->whereIn('quiz_id', $quizzes->pluck('id'))->get()->all(),
];
$backupPath = storage_path('app/private/dominant-before-two-groups-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json');
if (file_put_contents($backupPath, json_encode($backup, JSON_THROW_ON_ERROR)) === false) {
    throw new RuntimeException('Could not save Dominant backup.');
}
echo 'Saved Dominant backup: '.$backupPath.PHP_EOL;
unset($backup, $questions, $attempts, $quizzes);
DB::transaction(function (): void {
    require __DIR__.'/build_dominant_chord_extensions.php';
    $removed = Quiz::where('category', 'Dominant Chord Extensions')->whereIn('title', [
        'Dominant Chord Extentions · Level 5', 'Dominant Chord Extentions · Level 6', 'Dominant Chord Extentions · Level 7',
    ])->delete();
    echo 'Removed '.$removed.' obsolete Dominant levels; four levels remain.'.PHP_EOL;
    require __DIR__.'/verify_chord_extensions.php';
    require __DIR__.'/build_basic_chord_naming.php';
    require __DIR__.'/build_chord_naming_level2.php';
    foreach ([BasicChordNamingSamples::TITLE => 18, BasicChordNamingSamples::LEVEL_TWO_TITLE => 216] as $title => $expected) {
        $quiz = Quiz::where('title', $title)->firstOrFail();
        if ($quiz->questions()->count() !== $expected) {
            throw new RuntimeException('Incorrect Chord Naming question count: '.$title);
        }
        foreach ($quiz->questions()->with('audioVariants')->get() as $question) {
            if ($question->audioVariants->isEmpty()) {
                throw new RuntimeException('Orphan Chord Naming question');
            }
            foreach ($question->audioVariants as $audio) {
                if (BasicChordNamingSamples::resolve($audio->audio_path) === null) {
                    throw new RuntimeException('Unplayable Chord Naming audio');
                }
            }
        }
        echo $title.': '.$expected.' Group 1 structures verified.'.PHP_EOL;
    }
});
