<?php

namespace App\Console\Commands;

use App\Models\Quiz;
use App\Support\NoteSample;
use App\Support\QuizCatalog;
use App\Support\ReferenceAudioTracks;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class RebuildRelativeToneReferences extends Command
{
    protected $signature = 'audio:rebuild-relative-tone-references {quiz : 1 or 2} {--apply : Save the verified pool; default is audit only}';

    protected $description = 'Verify and rebuild one Relative Tone Pitch reference pool using the actual per-instrument question notes';

    public function handle(): int
    {
        if (! in_array($this->argument('quiz'), ['1', '2'], true)) {
            $this->error('Choose quiz 1 or 2.');

            return self::FAILURE;
        }
        config(['cache.default' => 'array']);
        $number = (int) $this->argument('quiz');
        $quiz = Quiz::where('title', 'Relative Tone Pitch #'.$number)->where('is_published', true)->firstOrFail();
        $tracks = [];
        $coverage = [];
        $questions = $quiz->questions()->with('audioVariants')->get();
        try {
            foreach ($questions as $question) {
                $row = [];
                foreach (QuizCatalog::SOUND_SLUGS as $sound => $slug) {
                    if ($number === 1) {
                        $path = $question->audioVariants->firstWhere('sound', $sound)?->audio_path;
                        if (! $path && $question->sound === $sound) {
                            $path = $question->audio_path;
                        }
                        if (! $path || NoteSample::resolve($path) === null) {
                            $notes = NoteSample::fromAnswer($question->answer, 'melodic', $slug);
                            if (! $notes || count($notes) !== 1) {
                                continue;
                            }
                            $path = NoteSample::path($slug, $notes[0]['degree'], $notes[0]['octave']);
                        }
                    } else {
                        $path = $question->audioVariants->firstWhere('sound', $sound)?->audio_path;
                        if (! $path && $question->sound === $sound) {
                            $path = $question->audio_path;
                        }
                        if (! $path) {
                            continue;
                        }
                    }
                    $notes = NoteSample::resolve($path);
                    if (! $notes || ! collect($notes)->every(fn (array $note): bool => $note['slug'] === $slug && NoteSample::sampleExists(NoteSample::path($slug, $note['degree'], $note['octave'])))) {
                        continue;
                    }
                    $track = [
                        'id' => 'rtp-'.$quiz->id.'-'.$question->id.'-'.$slug,
                        'clip_key' => 'rtp-'.$quiz->id.'-question-'.$question->id,
                        'attached_only' => true, 'label' => $question->answer,
                        'sound' => $sound, 'path' => $path,
                    ];
                    $references = new ReferenceAudioTracks;
                    $id = $references->pool([$track])[0]['id'];
                    $actual = $references->resolveOne([$track], $id, $sound);
                    $expected = array_map(fn (array $note): array => ['url' => NoteSample::url(NoteSample::path($slug, $note['degree'], $note['octave'])), 'offset' => $note['offset']], $notes);
                    if (($actual['notes'] ?? null) !== $expected) {
                        throw new RuntimeException('Pitch/register/instrument mismatch: '.$path);
                    }
                    $row[] = $track;
                    $coverage[$sound] = ($coverage[$sound] ?? 0) + 1;
                }
                if ($row === []) {
                    throw new RuntimeException('No playable source for question '.$question->id.'; old references preserved.');
                }
                $tracks = [...$tracks, ...$row];
            }
            if ($tracks === []) {
                throw new RuntimeException('No real question clips; old references preserved.');
            }
            $references = new ReferenceAudioTracks;
            $pool = $references->pool($tracks);
            if (count($pool) !== $questions->count()) {
                throw new RuntimeException('Distinct question rows collapsed in the pool.');
            }
            foreach ($pool as $clip) {
                $sources = array_values(array_filter($tracks, fn (array $track): bool => hash('sha256', 'clip-key'."\0".$track['clip_key']) === $clip['id']));
                $references = new ReferenceAudioTracks;
                foreach (QuizCatalog::SOUND_SLUGS as $sound => $slug) {
                    $expectedSource = collect($sources)->firstWhere('sound', $sound);
                    $actual = $references->resolveOne($sources, $clip['id'], $sound);
                    if (($actual !== null) !== ($expectedSource !== null)) {
                        throw new RuntimeException('Incorrect range coverage for '.$sound);
                    }
                }
            }
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
        $run = now()->format('Ymd-His').'-rtp'.$number.'-'.bin2hex(random_bytes(3));
        $directory = 'reference-pool-backups/'.$run;
        if ($this->option('apply')) {
            Storage::disk('local')->put($directory.'/'.$quiz->id.'.json', json_encode([
                'id' => $quiz->id, 'title' => $quiz->title, 'reference_audio_tracks' => $quiz->reference_audio_tracks,
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            $quiz->update(['reference_audio_tracks' => $tracks]);
        }
        $report = ['title' => $quiz->title, 'clips' => count($pool), 'verified_instrument_clips' => count($tracks), 'coverage' => $coverage, 'applied' => (bool) $this->option('apply')];
        Storage::disk('local')->put($directory.'/report.json', json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        $this->info(json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        $this->info('Report: '.Storage::disk('local')->path($directory.'/report.json'));

        return self::SUCCESS;
    }
}
