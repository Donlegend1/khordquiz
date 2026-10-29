<?php

use App\Models\QuizQuestionAudio;
use App\Support\NoteSample;
use App\Support\QuizCatalog;
use Illuminate\Container\Container;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

require_once __DIR__.'/../../vendor/autoload.php';

if (! Container::getInstance()->bound('db')) {
    $app = require __DIR__.'/../../bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
}

/** Default: Organ. Select Harpejji or Voice with --instrument; --apply renders and attaches. */
$sound = 'Organ';
foreach ($_SERVER['argv'] ?? [] as $argument) {
    if (str_starts_with($argument, '--instrument=')) {
        $sound = substr($argument, strlen('--instrument='));
    }
}
if (! in_array($sound, ['Organ', 'Harpejji', 'Voice'], true)) {
    throw new InvalidArgumentException('Supported instruments: Organ, Harpejji, Voice (Solo Choir).');
}
$slug = QuizCatalog::SOUND_SLUGS[$sound];
$rows = QuizQuestionAudio::query()
    ->where('sound', $sound)
    ->whereHas('question.quiz', fn ($query) => $query->where('title', 'Chordal melodies'))
    ->get();

$files = [];
foreach ($rows as $row) {
    if (preg_match('#^melodic-dictation/chordal-melodies/'.$slug.'/p\d+-(orig|drop2)-up12\.wav$#', $row->audio_path)) {
        continue;
    }
    if (! preg_match('#^melodic-dictation/chordal-melodies/'.$slug.'/p\d+-(orig|drop2)\.wav$#', $row->audio_path)) {
        throw new RuntimeException("Unrecognized {$sound} chordal path: {$row->audio_path}");
    }

    $newPath = substr($row->audio_path, 0, -4).'-up12.wav';
    $notes = NoteSample::resolve($newPath);
    if ($notes === null) {
        throw new RuntimeException("Missing recorded {$sound} notes for {$newPath}; no attachments changed.");
    }

    $files[] = [
        'audio_id' => $row->id,
        'original_path' => $row->audio_path,
        'output_path' => $newPath,
        'notes' => array_map(fn (array $note) => [
            'path' => NoteSample::path($note['slug'], $note['degree'], $note['octave']),
            'offset' => $note['offset'],
        ], $notes),
    ];
}

if ($files === []) {
    echo "No {$sound} Chordal melodies attachments need raising.".PHP_EOL;

    return;
}

$manifestPath = storage_path("app/private/{$slug}_chordal_melodies_octave_up.json");
file_put_contents($manifestPath, json_encode(['files' => $files], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo 'Prepared '.count($files)." octave-up {$sound} files: {$manifestPath}".PHP_EOL;

if (! in_array('--apply', $_SERVER['argv'] ?? [], true)) {
    return;
}

$renderer = new Process(['python3', __DIR__.'/render_live_chordal_manifest.py', $manifestPath]);
$renderer->setTimeout(300);
$renderer->mustRun(fn ($type, $output) => print $output);

DB::transaction(function () use ($files, $sound): void {
    foreach ($files as $file) {
        if (! is_file(storage_path('app/public/'.$file['output_path']))) {
            throw new RuntimeException('Rendered output missing: '.$file['output_path']);
        }
        $updated = QuizQuestionAudio::query()
            ->whereKey($file['audio_id'])
            ->where('sound', $sound)
            ->where('audio_path', $file['original_path'])
            ->update(['audio_path' => $file['output_path']]);
        if ($updated !== 1) {
            throw new RuntimeException('Attachment changed during rendering; updates rolled back.');
        }
    }
});

echo 'Raised '.count($files)." {$sound} Chordal melodies attachments by one octave. Original files preserved.".PHP_EOL;
