<?php

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;
use App\Support\BasicChordNamingSamples;
use Illuminate\Support\Facades\DB;

config(['cache.default' => 'array']);
$quizIds = Quiz::where('category', 'Minor Chord Extensions')->pluck('id');
$questions = QuizQuestion::whereIn('quiz_id', $quizIds)->get();
$attempts = QuizAttempt::whereIn('quiz_id', $quizIds)->get();
$backup = [
    'quizzes' => Quiz::whereIn('id', $quizIds)->get()->map->getAttributes()->all(),
    'questions' => $questions->map->getAttributes()->all(),
    'audio' => QuizQuestionAudio::whereIn('quiz_question_id', $questions->pluck('id'))->get()->map->getAttributes()->all(),
    'attempts' => $attempts->map->getAttributes()->all(),
    'answers' => DB::table('quiz_attempt_answers')->whereIn('quiz_attempt_id', $attempts->pluck('id'))->get()->all(),
];
$backupPath = storage_path('app/private/minor-before-tone-regrouping-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json');
if (file_put_contents($backupPath, json_encode($backup, JSON_THROW_ON_ERROR)) === false) {
    throw new RuntimeException('Could not save Minor backup.');
}
echo 'Saved Minor backup: '.$backupPath.PHP_EOL;
unset($backup, $questions, $attempts);
DB::transaction(function (): void {
    require __DIR__.'/build_minor_chord_extensions.php';
    require __DIR__.'/verify_chord_extensions.php';
    echo 'Minor extension groups verified; updating Group 1 Chord Naming pools.'.PHP_EOL;
    require __DIR__.'/build_basic_chord_naming.php';
    require __DIR__.'/build_chord_naming_level2.php';
    foreach ([BasicChordNamingSamples::TITLE => 16, BasicChordNamingSamples::LEVEL_TWO_TITLE => 192] as $title => $expected) {
        $quiz = Quiz::where('title', $title)->firstOrFail();
        if ($quiz->questions()->count() !== $expected) {
            throw new RuntimeException('Incorrect question count: '.$title);
        }
        foreach ($quiz->questions()->with('audioVariants')->get() as $question) {
            if ($question->audioVariants->isEmpty()) {
                throw new RuntimeException('Orphan Chord Naming question');
            }
            foreach ($question->audioVariants as $audio) {
                if (str_contains($audio->audio_path, 'minor-minadd911') || BasicChordNamingSamples::resolve($audio->audio_path) === null) {
                    throw new RuntimeException('Incorrect Group 1 Chord Naming attachment');
                }
            }
        }
        echo $title.': '.$expected.' questions verified.'.PHP_EOL;
    }
});
