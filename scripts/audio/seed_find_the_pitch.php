<?php

use App\Models\Quiz;
use App\Support\NoteSample;
use Illuminate\Container\Container;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/../../vendor/autoload.php';

if (! Container::getInstance()->bound('db')) {
    if (! defined('LARAVEL_START')) {
        define('LARAVEL_START', microtime(true));
    }
    $app = require __DIR__.'/../../bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
}

DB::transaction(function (): void {
    $quiz = Quiz::firstOrCreate(['title' => 'Find the Pitch'], [
        'slug' => Quiz::uniqueSlug('Find the Pitch'),
        'category' => 'Relative Pitch',
        'difficulty' => 'Intermediate',
        'description' => 'Identify the exact note and octave you hear.',
        'question_count' => 25,
        'is_published' => true,
    ]);

    $position = 0;
    foreach (NoteSample::SCIENTIFIC_PITCH_OCTAVES as $octave) {
        foreach (array_keys(NoteSample::CHROMATIC_SLUGS) as $label) {
            $answer = "{$label}{$octave}";
            $position++;
            $quiz->questions()->firstOrCreate(['answer' => $answer], [
                'position' => $position,
                'prompt' => 'Listen to the note and tap it on the keyboard.',
                'choices' => [$answer],
                'audio_path' => null,
                'sound' => null,
            ]);
        }
    }

    $quiz->questions()->where('prompt', 'Listen to the note and identify its exact pitch, including octave.')
        ->update(['prompt' => 'Listen to the note and tap it on the keyboard.']);

    $quiz->update(['question_count' => 25]);
    echo 'Find the Pitch ready: '.$quiz->questions()->count().' pitches; 25 questions per attempt.'.PHP_EOL;
});
