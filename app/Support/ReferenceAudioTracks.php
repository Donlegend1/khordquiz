<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReferenceAudioTracks
{
    private array $prepared = [];

    /**
     * Cheap listing for the member-facing Listen Mode picker: every distinct
     * reference clip this quiz has (including different keys, registers and
     * voicings with the same answer label), with no per-instrument resolution at all — safe to compute
     * on every category page load even for a quiz with a huge question pool
     * (hundreds to low thousands of distinct answers), since it never touches
     * NoteSample::resolve(). Resolving an actual clip's audio for the
     * selected instrument happens later, lazily, one at a time, only once
     * Listen Mode is open and only for the clip about to play — see
     * resolveGroup() via QuizController's reference-audio-clip route.
     *
     * @return list<array{id: string, label: string, name: ?string}>
     */
    public function pool(array $tracks): array
    {
        $result = [];
        foreach ($this->groups($tracks) as $id => $sources) {
            $result[] = ['id' => $id, 'label' => $sources[0]['label'], 'name' => $sources[0]['name'] ?? null];
        }

        return $result;
    }

    /**
     * Resolve exactly one clip's audio for one instrument — the lazy
     * counterpart to present()'s per-sound loop, called on demand for a
     * single Listen Mode "Next" press instead of for all 28 instruments at
     * once. Returns null if this group doesn't exist or this instrument has
     * no real/playable audio for it.
     *
     * @return array{url: ?string, name: ?string, notes: ?list<array{url: string, offset: float}>}|null
     */
    public function resolveOne(array $tracks, string $groupId, string $sound): ?array
    {
        $sources = $this->groups($tracks)[$groupId] ?? null;

        return $sources === null ? null : $this->resolveGroup($sources, $sound);
    }

    /**
     * @param  list<array<string, mixed>>  $tracks
     * @return array<string, list<array<string, mixed>>> keyed by this group's stable id
     */
    private function groups(array $tracks): array
    {
        $groups = [];
        foreach ($tracks as $track) {
            if (empty($track['path']) || empty($track['label']) || empty($track['sound'])) {
                continue;
            }
            $slug = QuizCatalog::SOUND_SLUGS[$track['sound']] ?? Str::slug($track['sound']);
            $recipe = str_replace('/'.$slug.'/', '/{instrument}/', $track['path']);
            $recipe = str_replace('/'.$slug.'.wav', '/{instrument}.wav', $recipe);
            $key = $track['label']."\0".($track['name'] ?? '')."\0".$recipe;
            // Some quiz rows use different recorded registers per instrument.
            // Their explicit clip key keeps these exact sources in one choice.
            if (! empty($track['clip_key'])) {
                $key = 'clip-key'."\0".$track['clip_key'];
            }
            $id = hash('sha256', $key);
            $groups[$id][] = $track;
        }

        return $groups;
    }

    /** @param  list<array<string, mixed>>  $sources one group's raw track rows, as grouped by groups() */
    private function resolveGroup(array $sources, string $sound): ?array
    {
        $slug = QuizCatalog::SOUND_SLUGS[$sound] ?? null;
        if ($slug === null) {
            return null;
        }

        $source = collect($sources)->firstWhere('sound', $sound);
        if ($source === null && ($sources[0]['attached_only'] ?? false)) {
            return null;
        }
        $source ??= $sources[0];
        $sourceSlug = QuizCatalog::SOUND_SLUGS[$source['sound']] ?? Str::slug($source['sound']);
        $path = str_replace('/'.$sourceSlug.'/', '/'.$slug.'/', $source['path']);
        $path = str_replace('/'.$sourceSlug.'.wav', '/'.$slug.'.wav', $path);
        // A path without an instrument segment can only belong to its
        // explicitly tagged instrument; never relabel someone else's clip.
        if ($path === $source['path'] && $sound !== $source['sound']) {
            return null;
        }

        $audio = $this->prepare($path, $slug, $source['label']);

        return $audio === null ? null : [
            'label' => $source['label'],
            'name' => $audio['name'] ?? $source['name'] ?? null,
            'url' => $audio['url'],
            'notes' => $audio['notes'],
        ];
    }

    private function prepare(string $path, string $slug, string $label): ?array
    {
        $key = $path."\0".$label;
        if (array_key_exists($key, $this->prepared)) {
            return $this->prepared[$key];
        }

        foreach (explode('/', $path) as $segment) {
            $instrument = str_ends_with($segment, '.wav') ? substr($segment, 0, -4) : $segment;
            if (in_array($instrument, [...array_values(QuizCatalog::SOUND_SLUGS), 'jazz-guitar', 'harp'], true) && $instrument !== $slug) {
                return $this->prepared[$key] = null;
            }
        }

        $name = null;
        if (preg_match('#^extensions/chord-naming/[a-z-]+/(major|minor|diminished|augmented|dominant)-bare-none-0\.wav$#', $path, $match)) {
            $intervals = match ($match[1]) {
                'major' => [0, 4, 7], 'minor' => [0, 3, 7],
                'diminished' => [0, 3, 6], 'augmented' => [0, 4, 8],
                'dominant' => [0, 4, 7, 10],
            };
            $notes = $this->notesForIntervals($intervals, $slug);
        } elseif (str_starts_with($path, 'reference-scales/')) {
            $notes = $this->majorScale($slug);
            if ($notes !== null) {
                $first = $notes[0]['midi'];
                $octave = intdiv($first, 12) - 1;
                $name = 'C'.$octave.' to C'.($octave + 1).', ascending and descending';
                $notes = array_map(function (array $note): array {
                    unset($note['midi']);

                    return $note;
                }, $notes);
            }
        } elseif (preg_match('#^reference-intervals/pairs/(melodic|harmonic)/#', $path, $match)) {
            // Melodic/Harmonic Second-Seventh: each of this quiz's seven
            // answer options (e.g. "Do - Re" through "Ti - Do" for Second) as
            // its own separate reference clip — melodic plays the pair's two
            // notes one after another, harmonic plays them together — rather
            // than an unrelated scale recording. Same resolution the real
            // quiz questions use (see QuizController's default NoteSample::fromAnswer() dispatch).
            $notes = NoteSample::fromAnswer($label, $match[1], $slug);
            $notes = $notes === null ? null : array_map(fn (array $note): array => ['slug' => $slug, ...$note], $notes);
        } elseif (str_starts_with($path, 'reference-intervals/live-v1/')) {
            // The "Intervals" category's own reference pool (one clip per
            // real question row, all 12 keys — see
            // scripts/audio/build_interval_all_keys.php): the path already
            // fully encodes timing + exact root/second MIDI
            // (reference-intervals/live-v1/{melodic|harmonic}/{slug}/{root}-{second}.wav),
            // so NoteSample::resolve() alone gets both the right key AND the
            // right timing. Routing this through IntervalSamples::fromAnswer()
            // below instead would re-pick ITS OWN single best-fit root from
            // the answer name alone, silently discarding which of the 12
            // keys this particular clip was supposed to be, and would also
            // misread timing: its "contains '/harmonic-'" check never matches
            // this convention's '/harmonic/' path segment, so harmonic clips
            // would collapse to the same one melodic-sounding pitch.
            $notes = NoteSample::resolve($path);
        } elseif (str_starts_with($path, 'reference-intervals/')) {
            $timing = str_contains($path, '/harmonic-') ? 'harmonic' : 'melodic';
            $notes = IntervalSamples::fromAnswer($label, $timing, $slug);
            $notes = $notes === null ? null : array_map(fn (array $note): array => ['slug' => $slug, ...$note], $notes);
        } else {
            $notes = NoteSample::resolve($path);
        }

        if ($notes !== null) {
            foreach ($notes as $note) {
                if ($note['slug'] !== $slug || ! NoteSample::sampleExists(NoteSample::path($slug, $note['degree'], $note['octave']))) {
                    $notes = null;
                    break;
                }
            }
        }
        if ($notes) {
            return $this->prepared[$key] = [
                'url' => null,
                'name' => $name,
                'notes' => array_map(fn (array $note): array => [
                    'url' => NoteSample::url(NoteSample::path($slug, $note['degree'], $note['octave'])),
                    'offset' => $note['offset'],
                ], $notes),
            ];
        }

        return $this->prepared[$key] = Storage::disk('public')->exists($path)
            ? ['url' => NoteSample::url($path), 'notes' => null]
            : null;
    }

    private function majorScale(string $slug): ?array
    {
        return $this->notesForIntervals([0, 2, 4, 5, 7, 9, 11, 12, 11, 9, 7, 5, 4, 2, 0], $slug, 0.55);
    }

    private function notesForIntervals(array $intervals, string $slug, float $spacing = 0): ?array
    {
        $preferred = match ($slug) {
            'bass-guitar', 'upright-bass', 'tuba', 'bassoon' => 36,
            'violin', 'celesta', 'vibraphone', 'trumpet', 'steel-drum', 'flute', 'oboe', 'clarinet' => 60,
            default => 48,
        };
        $roots = range(24, 84, 12);
        usort($roots, fn (int $a, int $b): int => abs($a - $preferred) <=> abs($b - $preferred));
        foreach ($roots as $root) {
            $notes = [];
            foreach ($intervals as $index => $interval) {
                $note = NoteSample::fromRecordedMidiPitch($root + $interval, $slug);
                if ($note === null) {
                    continue 2;
                }
                $notes[] = ['slug' => $slug, ...$note, 'offset' => $index * $spacing, 'midi' => $root + $interval];
            }

            return $notes;
        }

        return null;
    }
}
