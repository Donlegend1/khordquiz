<?php

use Illuminate\Support\Facades\DB;

$count = DB::table('quizzes')
    ->where('title', 'Chord naming')
    ->whereIn('category', ['Dominant Chord Extensions', 'Extensions'])
    ->update(['category' => 'Chord Naming']);

echo 'Moved '.$count.' existing Chord naming quiz to Chord Naming; content and progress preserved.'.PHP_EOL;
