<?php

use App\Models\Quiz;
use Illuminate\Support\Facades\DB;

/** Move existing quiz rows without rebuilding questions, audio, or attempts. */
DB::transaction(function (): void {
    $quizzes = Quiz::query()->where('category', 'Extensions')->lockForUpdate()->get();
    $moved = 0;
    foreach ($quizzes as $quiz) {
        $category = match (true) {
            $quiz->title === 'Chord naming' => 'Chord Naming',
            preg_match('/^Chord Tone Recognition #\d+$/', $quiz->title) === 1 => 'Chord Tone Recognition',
            preg_match('/^Major Chord Extentions(?: · Level \d+)?$/u', $quiz->title) === 1 => 'Major Chord Extensions',
            preg_match('/^Minor Chord Extentions(?: · Level \d+)?$/u', $quiz->title) === 1 => 'Minor Chord Extensions',
            preg_match('/^Dominant Chord Extentions(?: · Level \d+)?$/u', $quiz->title) === 1 => 'Dominant Chord Extensions',
            default => null,
        };
        if ($category === null) {
            continue;
        }
        DB::table('quizzes')->where('id', $quiz->id)->update(['category' => $category]);
        $moved++;
        echo $quiz->title.' -> '.$category.PHP_EOL;
    }
    echo 'Moved '.$moved.' quizzes; questions, audio, slugs and progress preserved.'.PHP_EOL;
});
