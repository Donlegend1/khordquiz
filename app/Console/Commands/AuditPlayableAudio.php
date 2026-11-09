<?php

namespace App\Console\Commands;

use App\Models\Quiz;
use App\Support\NoteSample;
use App\Support\QuizCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class AuditPlayableAudio extends Command
{
    protected $signature = 'audio:audit-playable {--output= : Report JSON destination}';

    protected $description = 'Count distinct live-mix examples whose individual note files exist';

    public function handle(): int
    {
        config(['cache.default' => 'array']);
        $seen = [];
        $files = [];
        $instruments = [];
        $candidates = 0;
        $unresolved = 0;
        $missing = 0;
        $disk = Storage::disk('public');
        foreach (Quiz::query()->where('is_published', true)->where('question_count', '>', 0)->cursor() as $quiz) {
            foreach ($quiz->questions()->with('audioVariants')->lazy(200) as $question) {
                $paths = $question->audioVariants->pluck('audio_path')->all();
                if ($question->audio_path) {
                    $paths[] = $question->audio_path;
                }
                foreach (array_unique($paths) as $path) {
                    $candidates++;
                    $notes = NoteSample::resolve($path);
                    if (! $notes) {
                        $unresolved++;

                        continue;
                    }
                    $events = [];
                    foreach ($notes as $note) {
                        $file = NoteSample::path($note['slug'], $note['degree'], $note['octave']);
                        $files[$file] ??= $disk->exists($file);
                        if (! $files[$file]) {
                            $missing++;

                            continue 2;
                        }
                        $events[] = [$file, (float) $note['offset']];
                    }
                    sort($events);
                    $seen[hash('sha256', json_encode($events, JSON_THROW_ON_ERROR))] = true;
                    foreach ($notes as $note) {
                        $instruments[$note['slug']] = true;
                    }
                }
            }
            $this->line($quiz->title.': '.count($seen).' distinct examples so far');
        }
        $report = [
            'examples' => count($seen),
            'instruments' => count(array_intersect(array_keys($instruments), array_values(QuizCatalog::SOUND_SLUGS))),
            'audited_at' => now()->toIso8601String(),
            'candidates' => $candidates,
            'unresolved' => $unresolved,
            'missing_notes' => $missing,
            'method' => 'Distinct registered live-mix note-file and timing combinations across published quizzes. All required note files exist. Shared examples across lessons are counted once. Reference tracks and unregistered fallback combinations are excluded.',
        ];
        file_put_contents($this->option('output') ?: resource_path('js/data/playableAudioStats.json'), json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
        $this->info(json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }
}
