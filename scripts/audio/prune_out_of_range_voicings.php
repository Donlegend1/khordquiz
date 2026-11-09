<?php

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;
use App\Support\EleventhDegreeChordSamples;
use App\Support\NinthDegreeChordSamples;
use App\Support\NoteSample;
use App\Support\SecondaryEleventhChordSamples;
use App\Support\SecondaryNinthChordSamples;
use App\Support\ThirteenthDegreeChordSamples;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

config(['cache.default' => 'array']);
$apply = in_array('--apply', $_SERVER['argv'] ?? [], true);
$classes = [
    'ninth-degree-chords' => NinthDegreeChordSamples::class,
    'secondary-ninth-chords' => SecondaryNinthChordSamples::class,
    'eleventh-degree-chords' => EleventhDegreeChordSamples::class,
    'secondary-eleventh-chords' => SecondaryEleventhChordSamples::class,
    'thirteenth-degree-chords' => ThirteenthDegreeChordSamples::class,
];
$ranges = [];
$inspections = [];
/** Inspect the existing voicing builders without changing or compacting their notes. */
$inspect = function (?string $path) use ($classes, &$ranges, &$inspections): ?array {
    if ($path === null || ! preg_match('~^([^/]+)/(level[1-6]|general)/([a-z-]+)/([A-Za-z0-9-]+)\.wav$~', $path, $match) || ! isset($classes[$match[1]])) {
        return null;
    }
    if (array_key_exists($path, $inspections)) {
        return $inspections[$path];
    }
    [, $family, $levelName, $slug, $filename] = $match;
    $class = $classes[$family];
    $general = $levelName === 'general';
    $level = $general ? 0 : (int) substr($levelName, 5);
    $parts = explode('-', $filename);
    $key = 5;
    if ($general || $level % 2 === 0) {
        $key = array_search(array_shift($parts), ['c', 'db', 'd', 'eb', 'e', 'f', 'gb', 'g', 'ab', 'a', 'bb', 'b'], true);
        if ($key === false) {
            return $inspections[$path] = null;
        }
    }
    $quality = array_shift($parts);
    $constant = $general ? 'QUALITIES_GENERAL' : 'QUALITIES';
    $reflection = new ReflectionClass($class);
    if (! $reflection->hasConstant($constant)) {
        return $inspections[$path] = null;
    }
    $qualities = $reflection->getConstant($constant);
    if (! isset($qualities[$quality])) {
        return $inspections[$path] = null;
    }
    $method = $general ? 'twoHandsNoRoot' : ($level <= 2 ? 'rootPosition' : ($level <= 4 ? (str_contains($family, 'ninth') ? 'fixedBassUpperStructure' : 'fixedTriadUpperStructure') : (str_contains($family, 'ninth') ? 'twoHandsNoRoot' : 'avoidTheRoot')));
    $owner = $family === 'secondary-eleventh-chords' ? EleventhDegreeChordSamples::class : $class;
    $pitches = (new ReflectionMethod($owner, $method))->invoke(null, 48 + $key, $qualities[$quality], $parts);
    if (! $pitches) {
        return $inspections[$path] = null;
    }
    if (! isset($ranges[$slug])) {
        $recorded = array_values(array_filter(range(0, 127), fn (int $midi): bool => NoteSample::fromRecordedMidiPitch($midi, $slug) !== null));
        $ranges[$slug] = $recorded === [] ? [] : [min($recorded), max($recorded)];
    }
    if ($ranges[$slug] === []) {
        return $inspections[$path] = null;
    }
    [$low, $high] = $ranges[$slug];
    foreach (range(-120, 120, 12) as $shift) {
        if (min($pitches) + $shift >= $low && max($pitches) + $shift <= $high) {
            return $inspections[$path] = null;
        }
    }

    return $inspections[$path] = ['path' => $path, 'instrument' => $slug, 'pitches' => $pitches, 'recorded_range' => [$low, $high]];
};
$variants = [];
$questions = [];
$paths = [];
foreach ($classes as $prefix => $class) {
    QuizQuestionAudio::where('audio_path', 'like', $prefix.'/%')->orderBy('id')->chunkById(2000, function ($rows) use ($inspect, &$variants, &$paths): void {
        foreach ($rows as $row) {
            $result = $inspect($row->audio_path);
            if ($result !== null) {
                $variants[] = $row->getAttributes();
                $paths[$row->audio_path] = $result;
            }
        }
    });
    foreach (QuizQuestion::where('audio_path', 'like', $prefix.'/%')->get() as $question) {
        $result = $inspect($question->audio_path);
        if ($result !== null) {
            $questions[] = $question->getAttributes();
            $paths[$question->audio_path] = $result;
        }
    }
}
if (in_array('--include-unavailable-live', $_SERVER['argv'] ?? [], true)) {
    $knownIds = array_fill_keys(array_column($variants, 'id'), true);
    QuizQuestionAudio::whereIn('sound', ['Celesta', 'Vibraphone'])->orderBy('id')->chunkById(2000, function ($rows) use (&$variants, &$paths, &$knownIds): void {
        foreach ($rows as $row) {
            if (isset($knownIds[$row->id]) || Storage::disk('public')->exists($row->audio_path) || NoteSample::resolve($row->audio_path) !== null) {
                continue;
            }
            $variants[] = $row->getAttributes();
            $knownIds[$row->id] = true;
            $paths[$row->audio_path] = ['path' => $row->audio_path, 'instrument' => $row->sound === 'Celesta' ? 'celesta' : 'vibraphone', 'reason' => 'missing_samples_without_rendered_fallback'];
        }
    });
}
$references = [];
foreach (Quiz::get(['id', 'reference_audio_path', 'reference_audio_tracks']) as $quiz) {
    $oldTracks = $quiz->reference_audio_tracks ?? [];
    $tracks = array_values(array_filter($oldTracks, function (array $track) use ($inspect, &$paths): bool {
        $result = $inspect($track['path'] ?? null) ?? ($paths[$track['path'] ?? ''] ?? null);
        if ($result !== null) {
            $paths[$track['path']] = $result;
        }

        return $result === null;
    }));
    $result = $inspect($quiz->reference_audio_path) ?? ($paths[$quiz->reference_audio_path ?? ''] ?? null);
    if ($result !== null) {
        $paths[$quiz->reference_audio_path] = $result;
    }
    if ($tracks !== $oldTracks || $result !== null) {
        $references[] = ['id' => $quiz->id, 'before' => $quiz->getAttributes(), 'tracks' => $tracks, 'path' => $result === null ? $quiz->reference_audio_path : null];
    }
}
foreach (Storage::disk('public')->allFiles() as $path) {
    if (isset($classes[explode('/', $path)[0]])) {
        $result = $inspect($path);
        if ($result !== null) {
            $paths[$path] = $result;
        }
    }
}
$questionIdsBySound = [];
foreach ($variants as $variant) {
    $questionIdsBySound[$variant['sound']][] = $variant['quiz_question_id'];
}
$sounds = array_values(array_unique(array_column($variants, 'sound')));
$attempts = QuizAttempt::where('status', QuizAttempt::STATUS_IN_PROGRESS)->whereIn('sound', $sounds)->get()->filter(fn (QuizAttempt $attempt): bool => array_intersect($attempt->question_sequence ?? [], $questionIdsBySound[$attempt->sound] ?? []) !== [])->values();
$files = array_values(array_filter(array_keys($paths), fn (string $path): bool => Storage::disk('public')->exists($path)));
$summary = ['instrument_variants' => count($variants), 'default_paths' => count($questions), 'reference_quizzes' => count($references), 'rendered_files' => count($files), 'in_progress_attempts' => $attempts->count(), 'by_instrument' => array_count_values(array_column($paths, 'instrument')), 'missing_samples_without_fallback' => count(array_filter($paths, fn (array $row): bool => isset($row['reason'])))];
echo json_encode($summary, JSON_PRETTY_PRINT).PHP_EOL;
if (! $apply) {
    echo 'Preview only. Pass --apply to remove these instrument-specific voicings.'.PHP_EOL;

    return;
}
$backup = ['summary' => $summary, 'variants' => $variants, 'questions' => $questions, 'references' => $references, 'paths' => array_values($paths), 'attempts' => $attempts->map->getAttributes()->all(), 'answers' => DB::table('quiz_attempt_answers')->whereIn('quiz_attempt_id', $attempts->pluck('id'))->get()->all()];
if (! app()->runningUnitTests()) {
    $backupPath = storage_path('app/private/out-of-range-voicings-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json');
    if (file_put_contents($backupPath, json_encode($backup, JSON_THROW_ON_ERROR)) === false) {
        throw new RuntimeException('Could not save the range-cleanup backup.');
    }
    echo 'Backup: '.$backupPath.PHP_EOL;
}
DB::transaction(function () use ($variants, $questions, $references, $attempts): void {
    foreach (array_chunk(array_column($variants, 'id'), 500) as $ids) {
        QuizQuestionAudio::whereIn('id', $ids)->delete();
    }
    foreach (array_chunk(array_column($questions, 'id'), 500) as $ids) {
        QuizQuestion::whereIn('id', $ids)->update(['audio_path' => null]);
    }
    foreach ($references as $reference) {
        Quiz::findOrFail($reference['id'])->update(['reference_audio_tracks' => $reference['tracks'], 'reference_audio_path' => $reference['path']]);
    }
    foreach ($attempts as $attempt) {
        $attempt->delete();
    }
});
foreach ($files as $file) {
    if (! Storage::disk('public')->delete($file)) {
        throw new RuntimeException('Could not delete excluded audio: '.$file);
    }
}
echo 'Removed only instrument-specific voicings that cannot fit any whole-octave placement inside the recorded range.'.PHP_EOL;
