<?php

namespace App\Support;

class MobileCatalog
{
    public const QUIZ_LENGTH = 25;

    public const LEVELS = ['Beginner', 'Intermediate', 'Advanced'];

    /**
     * @var array<string, list<string>>
     */
    public const PATHS = [
        'Beginner' => ['Relative Pitch', 'Intervals', 'Basic Triads', 'Melodic Dictation'],
        'Intermediate' => ['Add 9 & b9', '7th Degree Chords', 'Secondary 7th Chords', 'Chord Progressions', 'Scales'],
        'Advanced' => ['9th Degree Chords', 'Secondary 9th Chords', '11th Degree Chords', 'Secondary 11th Chords', '13th Degree Chords', 'Extentions recognition', 'Modal Voicings'],
    ];

    /**
     * @return list<string>
     */
    public static function categories(): array
    {
        return [
            'Relative Pitch',
            'Melodic Dictation',
            'Intervals',
            'Basic Triads',
            'Add 9 & b9',
            '7th Degree Chords',
            'Secondary 7th Chords',
            '9th Degree Chords',
            'Secondary 9th Chords',
            '11th Degree Chords',
            'Secondary 11th Chords',
            '13th Degree Chords',
            'Extentions recognition',
            'Chord Progressions',
            'Modal Voicings',
            'Scales',
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function subcategoryNames(): array
    {
        return [
            'Relative Pitch' => ['Find the Note', 'Relative Tone Pitch #1', 'Relative Tone Pitch #2', 'Relative Pitch — 2nd', 'Harmonic 2nd', 'Relative Pitch — 3rd', 'Harmonic 3rd', 'Relative Pitch — 4th', 'Harmonic 4th', 'Relative Pitch — 5th', 'Harmonic 5th', 'Relative Pitch — 6th', 'Harmonic 6th', 'Relative Pitch — 7th', 'Harmonic 7th', 'Find the Key #1', 'Find the Key #2'],
            'Melodic Dictation' => ['3-Note Melody', '4-Note Melody', '5-Note Melody', 'Dual Tonic Melody', 'Chordal Melody'],
            'Intervals' => ['Diatonic Intervals', 'Harmonic Diatonic Intervals', 'Non-diatonic Intervals', 'Harmonic Non-diatonic Intervals', 'Intervals - General', 'Harmonic Intervals: General', 'Interval - Patterns'],
            'Basic Triads' => ['C Root Position', 'Root Position — 12 Keys', 'C Drop 2 — Root Position', 'Drop 2 Root Position — 12 Keys', 'All Inversions — C', 'All Inversions — 12 Keys', 'Drop 2 All Inversions — C', 'Drop 2 All Inversions — 12 Keys'],
            'Add 9 & b9' => ['Add 9 & b9 · Level 1', 'Add 9 & b9 · Level 2', 'Add 9 & b9 · Level 3', 'Add 9 & b9 · Level 4'],
            '7th Degree Chords' => ['7th Degree Chords · Level 1', '7th Degree Chords · Level 2', '7th Degree Chords · Level 3', '7th Degree Chords · Level 4', '7th Degree Chords · Level 5', '7th Degree Chords · Level 6', '7th Degree Chords · Level 7', '7th Degree Chords · Level 8'],
            'Secondary 7th Chords' => ['Secondary 7th Chords · Level 1', 'Secondary 7th Chords · Level 2', 'Secondary 7th Chords · Level 3', 'Secondary 7th Chords · Level 4', 'Secondary 7th Chords · Level 5', 'Secondary 7th Chords · Level 6', 'Secondary 7th Chords · Level 7', 'Secondary 7th Chords · Level 8', 'Secondary 7th Chords · Level 9'],
            '9th Degree Chords' => ['9th Degree Chords · Level 1', '9th Degree Chords · Level 2', '9th Degree Chords · Level 3', '9th Degree Chords · Level 4'],
            '11th Degree Chords' => ['11th Degree Chords · Level 1', '11th Degree Chords · Level 2', '11th Degree Chords · Level 3', '11th Degree Chords · Level 4'],
            '13th Degree Chords' => ['13th Degree Chords · Level 1', '13th Degree Chords · Level 2', '13th Degree Chords · Level 3', '13th Degree Chords · Level 4'],
            'Extentions recognition' => ['Extentions recognition · Level 1', 'Extentions recognition · Level 2', 'Extentions recognition · Level 3', 'Extentions recognition · Level 4'],
            'Chord Progressions' => ['Chord Progressions · Level 1', 'Diatonic Progression', 'Build the Progression', 'Build the 7th Progression', 'Chord Progressions · Level 4', 'Chord Progressions · Level 5', 'Chord Progressions · Level 6', 'Chord Progressions · Level 7'],
            'Modal Voicings' => ['Modal Voicings · Level 1', 'Modal Voicings · Level 2', 'Modal Voicings · Level 3', 'Modal Voicings · Level 4'],
            'Scales' => ['Scales · Level 1', 'Scales · Level 3', 'Scales · Level 4', 'Scales · Level 5', 'Scales · Level 6', 'Scales · Level 7', 'Scales · Level 8'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'Diatonic Intervals' => 'Melodic Diatonic Intervals',
            'Non-diatonic Intervals' => 'Melodic Non-diatonic Intervals',
            'Intervals - General' => 'Melodic Intervals: General',
            'Relative Pitch — 2nd' => 'Melodic 2nd',
            'Relative Pitch — 3rd' => 'Melodic 3rd',
            'Relative Pitch — 4th' => 'Melodic 4th',
            'Relative Pitch — 5th' => 'Melodic 5th',
            'Relative Pitch — 6th' => 'Melodic 6th',
            'Relative Pitch — 7th' => 'Melodic 7th',
            'C Root Position' => 'Basic Triads · Level 1',
            'Root Position — 12 Keys' => 'Basic Triads · Level 2',
            'C Drop 2 — Root Position' => 'Basic Triads · Level 3',
            'Drop 2 Root Position — 12 Keys' => 'Basic Triads · Level 4',
            'All Inversions — C' => 'Basic Triads · Level 5',
            'All Inversions — 12 Keys' => 'Basic Triads · Level 6',
            'Drop 2 All Inversions — C' => 'Basic Triads · Level 7',
            'Drop 2 All Inversions — 12 Keys' => 'Basic Triads · Level 8',
            'Add 9 & b9 · Level 1' => 'Add 9 · Level 1',
            'Add 9 & b9 · Level 2' => 'Add 9 · Level 2',
            'Add 9 & b9 · Level 3' => 'Add 9 · Level 3',
            'Add 9 & b9 · Level 4' => 'Add 9 · Level 4',
            'Secondary 7th Chords · Level 9' => 'General',
            'Extentions recognition · Level 1' => 'Major Chord Extentions',
            'Extentions recognition · Level 2' => 'Minor Chord Extentions',
            'Extentions recognition · Level 3' => 'Dominant Chord Extentions',
            'Extentions recognition · Level 4' => 'Chord naming',
            'Chord Progressions · Level 1' => 'Cadences',
            'Chord Progressions · Level 4' => 'Passing Progression',
            'Chord Progressions · Level 5' => 'Progression recognition',
            'Chord Progressions · Level 6' => '2-5-1 chord degree',
            'Chord Progressions · Level 7' => 'Dom resolution',
            'Scales · Level 1' => 'Tonal Modes',
            'Scales · Level 3' => 'Major mode',
            'Scales · Level 4' => 'Dorian Mode',
            'Scales · Level 5' => 'Lydian Mode',
            'Scales · Level 6' => 'Locrian Mode',
            'Scales · Level 7' => 'Phrygian Mode',
            'Scales · Level 8' => 'Mixolydian Mode',
            'Build the Progression' => 'Progression Build #1',
            'Build the 7th Progression' => 'Progression Build #2',
        ];
    }

    public static function slug(string $name): string
    {
        return trim((string) preg_replace('/\s+/', ' ', str_replace('#', 'no', $name)));
    }

    /**
     * @return list<array{category: string, name: string, slug: string, question_count: int, sounds: list<string>}>
     */
    public static function quizzes(?string $category = null): array
    {
        $categories = $category === null ? self::categories() : [$category];
        $quizzes = [];

        foreach ($categories as $name) {
            foreach (self::rawNames($name) as $raw) {
                $slug = self::slug($raw);
                $quizzes[] = [
                    'category' => $name,
                    'name' => self::labels()[$raw] ?? $raw,
                    'slug' => $slug,
                    'question_count' => self::QUIZ_LENGTH,
                    'sounds' => self::sounds($slug, $name),
                ];
            }
        }

        return $quizzes;
    }

    /**
     * @return array{category: string, name: string, slug: string, question_count: int, sounds: list<string>}|null
     */
    public static function find(string $slug): ?array
    {
        foreach (self::quizzes() as $quiz) {
            if ($quiz['slug'] === $slug) {
                return $quiz;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $completed
     * @return array{done_share: float, total: int, remaining: list<array<string, mixed>>, up_next: array<string, mixed>|null}
     */
    public static function learningPath(string $level, array $completed): array
    {
        $categories = self::PATHS[$level] ?? self::PATHS['Beginner'];
        $path = [];

        foreach ($categories as $category) {
            foreach (self::quizzes($category) as $quiz) {
                $path[] = $quiz;
            }
        }

        $done = array_flip($completed);
        $remaining = array_values(array_filter($path, fn (array $quiz) => ! isset($done[$quiz['slug']])));

        return [
            'done_share' => $path === [] ? 0 : round((count($path) - count($remaining)) / count($path), 4),
            'total' => count($path),
            'remaining' => $remaining,
            'up_next' => $remaining[0] ?? null,
        ];
    }

    /**
     * @return list<string>
     */
    private static function rawNames(string $category): array
    {
        return self::subcategoryNames()[$category] ?? array_map(
            fn (string $level) => "{$category} · {$level}",
            ['Level 1', 'Level 2', 'Level 3', 'Level 4', 'Level 5'],
        );
    }

    /**
     * @return list<string>
     */
    public static function sounds(string $slug, ?string $category = null): array
    {
        $keyFinding = ['Monophonic', 'Vocal Stems', 'Instruments', 'Ensemble', 'Full Song', 'All'];
        if (in_array($slug, [self::slug('Find the Key #1'), self::slug('Find the Key #2')], true)) {
            return $keyFinding;
        }

        foreach (['2nd', '3rd', '4th', '5th', '6th', '7th'] as $interval) {
            if ($slug === self::slug("Harmonic {$interval}")) {
                return ['Piano', 'Violin', 'Strings', 'Guitar', 'Xylophone', 'Choir'];
            }
        }

        $chords = ['Piano', 'Guitar', 'Strings', 'Choir', 'Organ', 'Xylophone'];
        $single = ['Piano', 'Violin', 'Saxophone', 'Xylophone', 'Guitar', 'Voice'];

        return match ($category) {
            'Basic Triads', 'Add 9 & b9', '7th Degree Chords', 'Secondary 7th Chords', '9th Degree Chords', 'Secondary 9th Chords', '11th Degree Chords', 'Secondary 11th Chords', '13th Degree Chords', 'Extentions recognition', 'Chord Progressions', 'Modal Voicings' => $chords,
            'Intervals' => ['Piano', 'Violin', 'Strings', 'Guitar', 'Xylophone', 'Choir'],
            'Melodic Dictation', 'Relative Pitch', 'Scales' => $single,
            default => ['Piano', 'Violin', 'Saxophone', 'Guitar', 'Synth'],
        };
    }
}
