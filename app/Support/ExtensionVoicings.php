<?php

namespace App\Support;

class ExtensionVoicings
{
    /**
     * @param  list<int>  $base  root-relative close-position intervals
     * @return array<string, list<int>> v1..v5 => root-relative pitches
     */
    public static function forIntervals(array $base, int $third = 4): array
    {
        static $cache = [];
        $key = $third.':'.implode(',', $base);

        return $cache[$key] ??= self::generate($base, $third);
    }

    /** @return array<string, list<int>> */
    private static function generate(array $base, int $third): array
    {
        // Some of these chords skip a tone (e.g. Major add ♯11 jumps
        // straight from the 5th to the ♯11 with nothing between — an
        // 11-semitone gap), so even the plain close-position stack can
        // violate the ≤7-semitone adjacency rule before any doubling is
        // applied. v1 needs the same gap-filling pass as every other
        // variant, not just the ones built from an explicit double/open
        // move.
        $v1 = self::fillGaps($base, $base);
        if ($v1 === null) {
            return [];
        }
        $results = ['v1' => $v1];
        $seen = [self::signature($v1)];

        // Candidate move-chains, tried in this priority order (roughly
        // simplest/most-foundational first). A chain is one or more
        // (type, tone) operations applied in sequence before the final
        // gap-fill pass — single moves alone aren't enough variety for the
        // sparsest chords (e.g. a bare triad + one distant extension):
        // many single moves there converge, after gap-filling, on the
        // exact same forced voicing, since there are only 3 base tones
        // available as fillers. Chaining 2 moves reaches configurations a
        // single move can't. Duplicate results (same final stack) are
        // skipped — a chord with little room under the ≤2-occurrence /
        // ≤3-doubled-tones / ≤7-semitone-gap rules may end up with fewer
        // than 5 truly distinct voicings, which is accepted rather than
        // padded with repeats.
        $top = max($base);
        $second = count($base) >= 2 ? $base[count($base) - 2] : null;
        $singles = [[['double', 0]], [['double', $third]], [['open', $third]], [['double', 7]], [['open', 7]], [['double', $top]], [['open', $top]]];
        $pairs = [
            [['double', 0], ['double', $third]], [['double', 0], ['double', 7]], [['double', $third], ['double', 7]],
            [['double', 0], ['open', $third]], [['double', 7], ['open', $third]], [['double', $third], ['open', 7]],
            [['open', $third], ['open', 7]], [['double', 0], ['double', $top]], [['double', $third], ['double', $top]],
        ];
        if ($second !== null && $second !== $third && $second !== 7) {
            $singles[] = [['double', $second]];
            $singles[] = [['open', $second]];
        }

        $slot = 2;
        foreach ([...$singles, ...$pairs] as $chain) {
            if ($slot > 5) {
                break;
            }
            if (! collect($chain)->every(fn ($m) => in_array($m[1], $base, true))) {
                continue;
            }
            $attempt = self::applyChain($base, $chain);
            if ($attempt === null) {
                continue;
            }
            $sig = self::signature($attempt);
            if (in_array($sig, $seen, true)) {
                continue;
            }
            $results["v{$slot}"] = $attempt;
            $seen[] = $sig;
            $slot++;
        }

        return $results;
    }

    private static function signature(array $stack): string
    {
        sort($stack);

        return implode(',', $stack);
    }

    /**
     * @param  list<array{0: string, 1: int}>  $chain
     * @return list<int>|null
     */
    private static function applyChain(array $base, array $chain): ?array
    {
        $stack = $base;
        foreach ($chain as [$type, $tone]) {
            $top = max($stack);
            if ($type === 'double') {
                $copy = $tone;
                while ($copy <= $top) {
                    $copy += 12;
                }
                $stack = array_merge($stack, [$copy]);
            } else { // open: remove the tone's lowest/natural occurrence, place a copy above the (new) top
                $removed = false;
                $without = [];
                foreach ($stack as $v) {
                    if (! $removed && $v === $tone) {
                        $removed = true;

                        continue;
                    }
                    $without[] = $v;
                }
                if (! $removed) {
                    return null;
                }
                $newTop = max($without);
                $copy = $tone;
                while ($copy <= $newTop) {
                    $copy += 12;
                }
                $stack = array_merge($without, [$copy]);
            }
            sort($stack);
        }

        return self::fillGaps($stack, $base);
    }

    /** @return list<int>|null */
    private static function fillGaps(array $stack, array $baseTones): ?array
    {
        sort($stack);
        $guard = 0;
        while (true) {
            if (++$guard > 20) {
                return null;
            }
            sort($stack);
            $gapIndex = null;
            for ($i = 0; $i < count($stack) - 1; $i++) {
                if ($stack[$i + 1] - $stack[$i] > 7) {
                    $gapIndex = $i;
                    break;
                }
            }
            if ($gapIndex === null) {
                return $stack;
            }

            $candidates = [];
            foreach ($baseTones as $t) {
                $pc = (($t % 12) + 12) % 12;
                if (self::countOf($stack, $pc) >= 2) {
                    continue;
                }
                $val = $t;
                while ($val <= $stack[$gapIndex]) {
                    $val += 12;
                }
                if ($val >= $stack[$gapIndex + 1]) {
                    continue;
                }
                if (self::distinctDoubled(array_merge($stack, [$val]), $baseTones) > 3) {
                    continue;
                }
                $candidates[] = $val;
            }
            if ($candidates === []) {
                return null;
            }
            sort($candidates);
            $stack[] = $candidates[0];
        }
    }

    private static function countOf(array $stack, int $pitchClass): int
    {
        return count(array_filter($stack, fn (int $v): bool => (($v % 12) + 12) % 12 === $pitchClass));
    }

    private static function distinctDoubled(array $stack, array $baseTones): int
    {
        $doubled = 0;
        foreach ($baseTones as $t) {
            if (self::countOf($stack, (($t % 12) + 12) % 12) >= 2) {
                $doubled++;
            }
        }

        return $doubled;
    }
}
