<?php

namespace App\Console\Commands;

use App\Models\Quiz;
use App\Support\NoteSample;
use App\Support\QuizCatalog;
use App\Support\ReferenceAudioTracks;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class RebuildReferencePools extends Command
{
    protected $signature = 'audio:rebuild-reference-pools {--category= : Limit to one category} {--quiz= : Limit to one quiz slug} {--apply : Save verified pools; otherwise audit only}';

    protected $description = 'Rebuild and verify reference pools from real question audio, one lesson at a time in category order';

    public function handle(): int
    {
        config(['cache.default' => 'array']);
        $categories = array_slice(Quiz::CATEGORIES, array_search('Basic Triads', Quiz::CATEGORIES, true));
        if ($this->option('category')) {
            if (! in_array($this->option('category'), $categories, true)) {
                $this->error('Only categories from Basic Triads onward are in scope.');

                return self::FAILURE;
            }
            $categories = [$this->option('category')];
        }
        $report = [];
        $run = now()->format('Ymd-His').'-'.bin2hex(random_bytes(3));
        foreach ($categories as $category) {
            $query = Quiz::query()->where('category', $category)->where('is_published', true)->where('question_count', '>', 0);
            if ($this->option('quiz')) {
                $query->where('slug', $this->option('quiz'));
            }
            $order = array_flip(QuizCatalog::subcategories($category));
            $quizzes = $query->get()->sortBy(fn (Quiz $quiz): array => [$order[$quiz->title] ?? PHP_INT_MAX, $quiz->title]);
            foreach ($quizzes as $quiz) {
                try {
                    $row = $this->rebuild($quiz, $run);
                } catch (\Throwable $exception) {
                    $this->error($quiz->title.': '.$exception->getMessage());

                    return self::FAILURE;
                }
                $report[] = $row;
                $this->info($quiz->title.': '.$row['questions'].' questions, '.$row['clips'].' clips, '.$row['verified_instrument_clips'].' verified instrument clips'.($this->option('apply') ? ' — saved' : ' — audit only'));
            }
        }
        Storage::disk('local')->put('reference-pool-backups/'.$run.'/report.json', json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        $this->info('Report: '.Storage::disk('local')->path('reference-pool-backups/'.$run.'/report.json'));

        return self::SUCCESS;
    }

    private function rebuild(Quiz $quiz, string $run): array
    {
        $oldClips = count((new ReferenceAudioTracks)->pool($quiz->reference_audio_tracks ?? []));
        $tracks = [];
        $seen = [];
        $verified = 0;
        $questions = 0;
        $unplayable = [];
        foreach ($quiz->questions()->with('audioVariants')->lazy(200) as $question) {
            $questions++;
            $candidates = $question->audioVariants->sortBy(fn ($audio): int => $audio->sound === 'Piano' ? 0 : 1)
                ->map(fn ($audio): array => ['sound' => $audio->sound, 'path' => $audio->audio_path])->all();
            if ($question->audio_path && $question->sound) {
                $candidates[] = ['sound' => $question->sound, 'path' => $question->audio_path];
            }
            $track = null;
            foreach ($candidates as $candidate) {
                if (! isset(QuizCatalog::SOUND_SLUGS[$candidate['sound']]) || ! $candidate['path'] || ! NoteSample::resolve($candidate['path'])) {
                    continue;
                }
                $candidate += ['id' => 'question-'.$question->id, 'label' => $question->answer];
                $references = new ReferenceAudioTracks;
                $pool = $references->pool([$candidate]);
                if ($pool === [] || ! ($references->resolveOne([$candidate], $pool[0]['id'], $candidate['sound'])['notes'] ?? null)) {
                    continue;
                }
                $track = $candidate;
                break;
            }
            if ($track === null) {
                $unplayable[] = $question->id;

                continue;
            }
            $references = new ReferenceAudioTracks;
            $id = $references->pool([$track])[0]['id'];
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $sourceSlug = QuizCatalog::SOUND_SLUGS[$track['sound']];
            foreach (QuizCatalog::SOUND_SLUGS as $sound => $slug) {
                $path = str_replace(['/'.$sourceSlug.'/', '/'.$sourceSlug.'.wav'], ['/'.$slug.'/', '/'.$slug.'.wav'], $track['path']);
                $notes = NoteSample::resolve($path);
                $playable = $notes && collect($notes)->every(fn (array $note): bool => $note['slug'] === $slug && NoteSample::sampleExists(NoteSample::path($slug, $note['degree'], $note['octave'])));
                $actual = $references->resolveOne([$track], $id, $sound);
                if (! $playable) {
                    if ($actual['notes'] ?? null) {
                        throw new RuntimeException('Unexpected resolution for '.$sound.' / '.$path);
                    }

                    continue;
                }
                $expected = array_map(fn (array $note): array => ['url' => NoteSample::url(NoteSample::path($slug, $note['degree'], $note['octave'])), 'offset' => $note['offset']], $notes);
                if (($actual['notes'] ?? null) !== $expected) {
                    throw new RuntimeException('Reference pitch/timing/instrument mismatch: '.$sound.' / '.$path);
                }
                $verified++;
            }
            $tracks[] = $track;
        }
        if ($unplayable !== []) {
            throw new RuntimeException('No live-playable source for question IDs: '.implode(',', $unplayable));
        }
        if ($tracks === []) {
            throw new RuntimeException('No real clips; existing reference pool preserved.');
        }
        $pool = (new ReferenceAudioTracks)->pool($tracks);
        if (count($pool) !== count($tracks)) {
            throw new RuntimeException('Distinct question clips collapsed in the reference pool.');
        }
        if ($this->option('apply')) {
            Storage::disk('local')->put('reference-pool-backups/'.$run.'/'.$quiz->id.'.json', json_encode([
                'id' => $quiz->id, 'title' => $quiz->title, 'reference_audio_tracks' => $quiz->reference_audio_tracks,
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            $quiz->update(['reference_audio_tracks' => $tracks]);
        }

        return ['quiz_id' => $quiz->id, 'title' => $quiz->title, 'category' => $quiz->category, 'questions' => $questions, 'old_clips' => $oldClips, 'clips' => count($pool), 'verified_instrument_clips' => $verified];
    }
}
