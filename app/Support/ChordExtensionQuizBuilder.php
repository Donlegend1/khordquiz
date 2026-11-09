<?php

namespace App\Support;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use Illuminate\Support\Facades\DB;

class ChordExtensionQuizBuilder
{
    public const CHOICES = ['6', '7', '♭9', '9', '11', '♯11', '♭13', '13'];

    public const DOMINANT_CHOICES = ['♭9', '9', '♯9', '11', '♯11', '♭13', '13'];

    private const KEYS = ['c', 'db', 'd', 'eb', 'e', 'f', 'gb', 'g', 'ab', 'a', 'bb', 'b'];

    /** @return array<int, array{group: int|list<int>, keys: list<string>, basic?: bool}> */
    public static function levels(string $family = 'Major'): array
    {
        $levels = [];
        foreach (range(1, $family === 'Dominant' ? 8 : 12) as $level) {
            $levels[$level] = ['group' => intdiv($level - 1, 4) + 1, 'keys' => $level % 2 === 1 ? ['f'] : self::KEYS, 'basic' => ($level - 1) % 4 < 2];
        }
        if ($family !== 'Dominant') {
            $levels[13] = ['group' => [1, 2, 3], 'keys' => self::KEYS, 'basic' => false];
        }

        return $levels;
    }

    /** @param list<int> $intervals */
    public static function answerFor(array $intervals, string $family = 'Major'): string
    {
        $mapping = [9 => '6', 10 => '7', 11 => '7', 13 => '♭9', 14 => '9', 17 => '11', 18 => '♯11', 20 => '♭13', 21 => '13'];
        if ($family === 'Dominant') {
            $mapping = [13 => '♭9', 14 => '9', 15 => '♯9', 17 => '11', 18 => '♯11', 20 => '♭13', 21 => '13'];
        }
        $choices = $family === 'Dominant' ? self::DOMINANT_CHOICES : self::CHOICES;
        $tokens = array_filter(array_map(fn (int $interval): ?string => $mapping[$interval] ?? null, $intervals));

        return implode(', ', array_values(array_intersect($choices, $tokens)));
    }

    /**
     * Prepare playable question/audio data without changing the database.
     *
     * @param  class-string<MajorChordExtensionSamples>|class-string<MinorChordExtensionSamples>|class-string<DominantChordExtensionSamples>  $resolver
     * @param  array{group: int|list<int>, keys: list<string>, basic?: bool}  $scope
     * @return list<array{answer: string, audio: list<array{sound: string, audio_path: string}>}>
     */
    public static function plan(string $family, string $resolver, array $scope): array
    {
        $prefix = strtolower($family).'-chord-extensions';
        $instruments = array_diff_key(QuizCatalog::SOUND_SLUGS, array_flip(['Bass Guitar', 'Tuba', 'Upright Bass']));
        $plan = [];
        foreach ((array) $scope['group'] as $sourceGroup) {
            foreach ($resolver::qualitiesForLevel($sourceGroup) as $quality => $intervals) {
                foreach ($scope['keys'] as $key) {
                    foreach (($scope['basic'] ?? false) ? [0] : range(1, 5) as $variant) {
                        $path = ($scope['basic'] ?? false)
                            ? 'plain-chord-extensions/'.strtolower($family).'/group'.$sourceGroup.'/%s/'.$key.'-'.$quality.'.wav'
                            : $prefix.'/level'.$sourceGroup.'/%s/'.$key.'-'.$quality.'-v'.$variant.'.wav';
                        $audio = [];
                        foreach ($instruments as $sound => $slug) {
                            $audioPath = sprintf($path, $slug);
                            if (NoteSample::resolve($audioPath) !== null) {
                                $audio[] = ['sound' => $sound, 'audio_path' => $audioPath];
                            }
                        }
                        if ($audio !== []) {
                            $plan[] = ['answer' => self::answerFor($intervals, $family), 'audio' => $audio];
                        }
                    }
                }
            }
        }

        return $plan;
    }

    /**
     * @param  class-string<MajorChordExtensionSamples>|class-string<MinorChordExtensionSamples>|class-string<DominantChordExtensionSamples>  $resolver
     * @return list<array{level: int, quiz_id: int, questions: int, attachments: int}>
     */
    public static function build(string $family, string $resolver, ?array $levels = null): array
    {
        $report = [];
        foreach ($levels ?? self::levels($family) as $level => $scope) {
            $plan = self::plan($family, $resolver, $scope);
            if ($plan === []) {
                throw new \RuntimeException('No playable questions for '.$family.' level '.$level);
            }
            $report[] = DB::transaction(function () use ($family, $level, $plan, $scope): array {
                $title = $family.' Chord Extentions · Level '.$level;
                $quiz = Quiz::where('title', $title)->first();
                if ($quiz === null && $level === 1) {
                    $quiz = Quiz::where('title', $family.' Chord Extentions')->first();
                }
                $quiz ??= new Quiz;
                $quiz->fill([
                    'title' => $title,
                    'slug' => $quiz->slug ?? Quiz::uniqueSlug($title),
                    'category' => $family.' Chord Extensions',
                    'difficulty' => $quiz->difficulty ?? 'Advanced',
                    'description' => $family.' chord extensions: '.(is_array($scope['group']) ? 'General review, all groups and all 12 keys.' : 'group '.$scope['group'].($scope['keys'] === ['f'] ? ', key of F' : ', all 12 keys').(($scope['basic'] ?? false) ? ', basic root-position structures only.' : ', voicing variations.')),
                    'question_count' => 25,
                    'is_published' => $quiz->exists ? $quiz->is_published : true,
                ])->save();
                $existing = $quiz->questions()->with('audioVariants')->get()->keyBy(function (QuizQuestion $question): string {
                    $path = $question->audioVariants->first()?->audio_path ?? '';

                    return preg_replace('#/[^/]+/([^/]+\.wav)$#', '/$1', $path);
                });
                DB::table('quiz_questions')->where('quiz_id', $quiz->id)->update(['position' => DB::raw('position + 10000')]);
                foreach ($existing as $question) {
                    $question->position += 10000;
                    $question->syncOriginalAttribute('position');
                }
                $kept = [];
                $attachments = 0;
                foreach ($plan as $index => $entry) {
                    $key = preg_replace('#/[^/]+/([^/]+\.wav)$#', '/$1', $entry['audio'][0]['audio_path']);
                    $question = $existing->get($key) ?? new QuizQuestion(['quiz_id' => $quiz->id]);
                    $question->fill([
                        'position' => $index + 1,
                        'prompt' => 'Listen to the chord and select the extension(s) you hear.',
                        'choices' => $family === 'Dominant' ? self::DOMINANT_CHOICES : self::CHOICES,
                        'answer' => $entry['answer'],
                    ])->save();
                    $question->audioVariants()->whereNotIn('sound', array_column($entry['audio'], 'sound'))->delete();
                    foreach ($entry['audio'] as $audio) {
                        $question->audioVariants()->updateOrCreate(['sound' => $audio['sound']], ['audio_path' => $audio['audio_path']]);
                        $attachments++;
                    }
                    $kept[] = $question->id;
                }
                $obsolete = $quiz->questions()->whereNotIn('id', $kept)->pluck('id')->all();
                if ($obsolete !== []) {
                    QuizAttempt::where('quiz_id', $quiz->id)->get()->each(function (QuizAttempt $attempt) use ($kept): void {
                        if ($attempt->question_sequence === null || array_diff($attempt->question_sequence, $kept) !== []) {
                            $attempt->delete();
                        }
                    });
                    $quiz->questions()->whereIn('id', $obsolete)->delete();
                }

                return ['level' => $level, 'quiz_id' => $quiz->id, 'questions' => count($plan), 'attachments' => $attachments];
            });
        }

        return $report;
    }
}
