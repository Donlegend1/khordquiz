<?php

namespace App\Support;

class PlainChordExtensionSamples
{
    private const KEYS = ['c', 'db', 'd', 'eb', 'e', 'f', 'gb', 'g', 'ab', 'a', 'bb', 'b'];

    /** @return list<array{slug: string, degree: string, octave: int, offset: float}>|null */
    public static function resolve(string $path, ?string $instrument = null): ?array
    {
        if (! preg_match('#^plain-chord-extensions/(major|minor|dominant)/group([123])/([a-z-]+)/([a-z]+)-([a-z0-9]+)\.wav$#', $path, $m)) {
            return null;
        }
        $source = match ($m[1]) {
            'major' => MajorChordExtensionSamples::class,
            'minor' => MinorChordExtensionSamples::class,
            'dominant' => DominantChordExtensionSamples::class,
        };
        $intervals = $source::qualitiesForLevel((int) $m[2])[$m[5]] ?? null;
        $key = array_search($m[4], self::KEYS, true);
        if ($intervals === null || $key === false) {
            return null;
        }
        $slug = $instrument ?? $m[3];
        $notes = [];
        foreach ($intervals as $interval) {
            $note = NoteSample::fromRecordedMidiPitch(48 + $key + $interval, $slug);
            if ($note === null) {
                return null;
            }
            $notes[] = ['slug' => $slug, ...$note];
        }

        return $notes;
    }
}
