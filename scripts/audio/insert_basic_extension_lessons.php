<?php

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;
use App\Support\ChordExtensionLessonRestructure;
use Illuminate\Support\Facades\DB;

config(['cache.default' => 'array']);
$families = $families ?? ['Major', 'Minor'];
$quizzes = Quiz::whereIn('category', array_map(fn (string $family): string => $family.' Chord Extensions', $families))->get();
$questions = QuizQuestion::whereIn('quiz_id', $quizzes->pluck('id'))->get();
$attempts = QuizAttempt::whereIn('quiz_id', $quizzes->pluck('id'))->get();
$backup = [
    'quizzes' => $quizzes->map->getAttributes()->all(),
    'questions' => $questions->map->getAttributes()->all(),
    'audio' => QuizQuestionAudio::whereIn('quiz_question_id', $questions->pluck('id'))->get()->map->getAttributes()->all(),
    'attempts' => $attempts->map->getAttributes()->all(),
    'answers' => DB::table('quiz_attempt_answers')->whereIn('quiz_attempt_id', $attempts->pluck('id'))->get()->all(),
];
$backupPath = storage_path('app/private/'.strtolower(implode('-', $families)).'-before-basic-lessons-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json');
if (file_put_contents($backupPath, json_encode($backup, JSON_THROW_ON_ERROR)) === false) {
    throw new RuntimeException('Could not save backup.');
}
echo 'Saved backup: '.$backupPath.PHP_EOL;
unset($backup, $questions, $attempts, $quizzes);
DB::transaction(function () use ($families): void {
    foreach (ChordExtensionLessonRestructure::apply($families) as $family => $levels) {
        foreach ($levels as $level) {
            echo $family.' basic Level '.$level['level'].': '.$level['questions'].' questions, '.$level['attachments'].' playable mixes.'.PHP_EOL;
        }
    }
    require __DIR__.'/verify_chord_extensions.php';
});
