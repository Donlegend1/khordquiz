<?php

namespace App\Support;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;
use Illuminate\Support\Facades\DB;

class ChordNamingQuizBuilder
{
    /** @return list<array{level: int, questions: int, mixes: int}> */
    public static function build(array $levels = [1, 2, 3, 4, 5, 6]): array
    {
        $report = [];
        foreach ($levels as $level) {
            if (! in_array($level, range(1, 6), true)) {
                throw new \InvalidArgumentException('Unknown Chord Naming level.');
            }
            $group = intdiv($level - 1, 2) + 1;
            $keys = $level % 2 === 1 ? ['c'] : BasicChordNamingSamples::KEYS;
            $manifest = BasicChordNamingSamples::manifest($keys, $group, true);
            $prefix = $group === 1 ? 'chord-naming/basic-v1/' : 'chord-naming/basic-group'.$group.'-v1/';
            $report[] = DB::transaction(function () use ($level, $manifest, $prefix): array {
                $title = 'Chord Naming · Level '.$level;
                $quiz = Quiz::where('title', $title)->first();
                if ($quiz === null && $level === 1) {
                    $quiz = Quiz::where('title', 'Chord naming')->first();
                }
                $quiz ??= new Quiz;
                if ($quiz->exists && ! app()->runningUnitTests()) {
                    self::backup($quiz, $level);
                }
                $quiz->fill([
                    'title' => $title, 'category' => 'Chord Naming',
                    'slug' => $quiz->slug ?? Quiz::uniqueSlug($title),
                    'difficulty' => $quiz->difficulty ?? 'Advanced',
                    'description' => 'Group '.(intdiv($level - 1, 2) + 1).' exact root-position structures'.($level >= 5 ? ' including secondary seventh chords' : '').($level % 2 === 1 ? ' in C' : ' across all 12 keys').'; includes No 3 and No 5 versions of seventh chords.',
                    'question_count' => 25, 'is_published' => $quiz->exists ? $quiz->is_published : true,
                ])->save();
                $existing = $quiz->questions()->with('audioVariants')->get();
                $byPath = $existing->keyBy(fn (QuizQuestion $question): string => self::identity($question->audioVariants->first()?->audio_path ?? $question->audio_path ?? ''));
                $position = (int) $existing->max('position');
                $kept = [];
                $mixes = 0;
                foreach ($manifest as $row) {
                    if (! ChordNamingAnswer::isValid($row['answer'])) {
                        throw new \RuntimeException('Invalid chord answer: '.$row['recipe']);
                    }
                    $filename = $row['key'].'-'.$row['recipe'].'.wav';
                    $identity = $prefix.$filename;
                    $question = $byPath->get($identity) ?? new QuizQuestion(['quiz_id' => $quiz->id, 'position' => ++$position]);
                    $question->fill(['prompt' => 'Listen to the chord and build its full name.', 'choices' => [], 'answer' => $row['answer']])->save();
                    foreach ($row['samples'] as $slug => $notes) {
                        $path = $prefix.$slug.'/'.$filename;
                        if (NoteSample::resolve($path) === null) {
                            throw new \RuntimeException('Unplayable attachment: '.$path);
                        }
                        $question->audioVariants()->updateOrCreate(['sound' => array_search($slug, QuizCatalog::SOUND_SLUGS, true)], ['audio_path' => $path]);
                        $mixes++;
                    }
                    $kept[] = $question->id;
                }
                $obsolete = $existing->pluck('id')->diff($kept);
                if ($obsolete->isNotEmpty()) {
                    QuizAttempt::where('quiz_id', $quiz->id)->get()->each(function (QuizAttempt $attempt) use ($kept): void {
                        if ($attempt->question_sequence === null || array_diff($attempt->question_sequence, $kept) !== []) {
                            $attempt->delete();
                        }
                    });
                    $quiz->questions()->whereIn('id', $obsolete)->delete();
                }

                return ['level' => $level, 'questions' => count($manifest), 'mixes' => $mixes];
            });
            if (! app()->runningUnitTests()) {
                $path = storage_path('app/private/chord-naming-level'.$level.'-mobile.json');
                if (file_put_contents($path, json_encode($manifest, JSON_THROW_ON_ERROR)) === false) {
                    throw new \RuntimeException('Could not save mobile chord data.');
                }
            }
        }

        return $report;
    }

    private static function identity(string $path): string
    {
        return preg_replace('#/[^/]+/([^/]+\.wav)$#', '/$1', $path);
    }

    private static function backup(Quiz $quiz, int $level): void
    {
        $backup = [
            'quiz' => $quiz->getAttributes(),
            'questions' => $quiz->questions()->get()->map->getAttributes()->all(),
            'audio' => QuizQuestionAudio::whereIn('quiz_question_id', $quiz->questions()->select('id'))->get()->map->getAttributes()->all(),
            'attempts' => $quiz->attempts()->get()->map->getAttributes()->all(),
            'answers' => DB::table('quiz_attempt_answers')->whereIn('quiz_attempt_id', $quiz->attempts()->select('id'))->get()->all(),
        ];
        $path = storage_path('app/private/chord-naming-level'.$level.'-before-omissions-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json');
        if (file_put_contents($path, json_encode($backup, JSON_THROW_ON_ERROR)) === false) {
            throw new \RuntimeException('Could not save chord naming backup.');
        }
        echo 'Saved backup: '.$path.PHP_EOL;
    }
}
