<?php

namespace App\Support;

use App\Models\Quiz;
use App\Models\QuizQuestionAudio;
use Illuminate\Support\Facades\DB;

class SingleKeyChordCoverage
{
    public const CATEGORIES = [
        'Basic Triads', 'Add 9', '7th Degree Chords', 'Secondary 7th Chords',
        '9th Degree Chords', 'Secondary 9th Chords', '11th Degree Chords',
        'Secondary 11th Chords', '13th Degree Chords',
    ];

    public static function isSingleKey(Quiz $quiz): bool
    {
        return in_array($quiz->category, self::CATEGORIES, true)
            && preg_match('/ · Level ([1357])$/u', $quiz->title) === 1;
    }

    /** @return array{lessons: int, questions: int, remove: list<int>, add: list<array{quiz_question_id: int, sound: string, audio_path: string}>} */
    public static function plan(): array
    {
        $result = ['lessons' => 0, 'questions' => 0, 'remove' => [], 'add' => []];
        $instruments = array_diff_key(QuizCatalog::SOUND_SLUGS, array_flip(['Bass Guitar', 'Upright Bass', 'Tuba']));
        foreach (Quiz::whereIn('category', self::CATEGORIES)->get(['id', 'title', 'category']) as $quiz) {
            if (! self::isSingleKey($quiz)) {
                continue;
            }
            $result['lessons']++;
            foreach ($quiz->questions()->with('audioVariants')->get() as $question) {
                $variants = $question->audioVariants;
                $template = $variants->firstWhere('sound', 'Piano')?->audio_path ?? $variants->first()?->audio_path ?? $question->audio_path;
                $playable = [];
                foreach ($variants as $variant) {
                    if (self::playable($variant->audio_path) && ! in_array($variant->sound, ['Bass Guitar', 'Upright Bass', 'Tuba'], true)) {
                        $playable[] = $variant->sound;
                    } else {
                        $result['remove'][] = $variant->id;
                    }
                }
                if ($template !== null) {
                    foreach ($instruments as $sound => $slug) {
                        if (in_array($sound, $playable, true)) {
                            continue;
                        }
                        $path = preg_replace('#/[^/]+/([^/]+\.wav)$#', '/'.$slug.'/$1', $template);
                        if (self::playable($path)) {
                            $result['add'][] = ['quiz_question_id' => $question->id, 'sound' => $sound, 'audio_path' => $path];
                            $playable[] = $sound;
                        }
                    }
                }
                if ($playable === []) {
                    throw new \RuntimeException('No playable F-based instrument for question '.$question->id.' in '.$quiz->title);
                }
                $result['questions']++;
            }
        }

        return $result;
    }

    private static function playable(?string $path): bool
    {
        $notes = $path ? NoteSample::resolve($path) : null;
        if (! $notes) {
            return false;
        }
        foreach ($notes as $note) {
            if (! NoteSample::sampleExists(NoteSample::path($note['slug'], $note['degree'], $note['octave']))) {
                return false;
            }
        }

        return true;
    }

    /** @param array{remove: list<int>, add: list<array{quiz_question_id: int, sound: string, audio_path: string}>} $plan */
    public static function apply(array $plan): void
    {
        DB::transaction(function () use ($plan): void {
            foreach (array_chunk($plan['remove'], 500) as $ids) {
                QuizQuestionAudio::whereIn('id', $ids)->delete();
            }
            foreach ($plan['add'] as $audio) {
                QuizQuestionAudio::updateOrCreate(
                    ['quiz_question_id' => $audio['quiz_question_id'], 'sound' => $audio['sound']],
                    ['audio_path' => $audio['audio_path']],
                );
            }
        });
    }
}
