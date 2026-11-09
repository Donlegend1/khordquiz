<?php

use App\Support\NoteSample;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$cacheStore = config('cache.default');
config(['cache.default' => 'array']);
$apply = in_array('--apply', $argv, true);

// Fill internal gaps only; retain each bank's existing outer pitch limits.
foreach (['bass-guitar', 'violin', 'trombone', 'cello'] as $slug) {
    $recorded = [];
    foreach (range(0, 127) as $midi) {
        $note = NoteSample::fromRecordedMidiPitch($midi, $slug);
        if ($note !== null) {
            $recorded[$midi] = NoteSample::path($slug, $note['degree'], $note['octave']);
        }
    }
    if ($recorded === []) {
        throw new RuntimeException('Missing source bank: '.$slug);
    }
    foreach (range(min(array_keys($recorded)), max(array_keys($recorded))) as $midi) {
        if (isset($recorded[$midi])) {
            continue;
        }
        $label = array_keys(NoteSample::CHROMATIC_SLUGS)[$midi % 12];
        $octave = intdiv($midi, 12) - 1 - (str_contains($label, '#') ? 1 : 0);
        $path = NoteSample::path($slug, NoteSample::equivalentDegree($label) ?? $label, $octave);
        $sourcePitches = array_keys($recorded);
        usort($sourcePitches, fn (int $a, int $b): int => abs($a - $midi) <=> abs($b - $midi));
        $sourcePitch = $sourcePitches[0];
        echo $path.' <- '.$recorded[$sourcePitch].' ('.($midi - $sourcePitch).' semitones)'.PHP_EOL;
        if ($apply) {
            $output = storage_path('app/public/'.$path);
            if (is_file($output)) {
                throw new RuntimeException('Refusing to replace existing sample: '.$path);
            }
            $temporary = $output.'.tmp.wav';
            $process = new Process(['sox', storage_path('app/public/'.$recorded[$sourcePitch]), $temporary, 'pitch', (string) (($midi - $sourcePitch) * 100)]);
            $process->mustRun();
            rename($temporary, $output);
        }
    }
    Cache::forget('note-sample-files:'.$slug);
    if ($apply) {
        Cache::store($cacheStore)->forget('note-sample-files:'.$slug);
    }
}
