<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Support\MobileCatalog;
use App\Support\NoteSample;
use App\Support\QuizAnswerPresets;
use App\Support\QuizCatalog;
use App\Support\TonalModeAllKeysSamples;
use App\Support\TonalModeScaleSamples;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TonalModeAllKeysLiveMixTest extends TestCase
{
    use RefreshDatabase;

    private const NAMES = ['Do', 'C#/Db', 'Re', 'D#/Eb', 'Mi', 'Fa', 'F#/Gb', 'So', 'G#/Ab', 'La', 'A#/Bb', 'Ti'];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Cache::flush();
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
        $full = (new \ReflectionClass(NoteSample::class))->getConstant('FULL_CHROMATIC_BANK_SLUGS');
        foreach (QuizCatalog::SOUND_SLUGS as $slug) {
            [$low, $high] = TonalModeScaleSamples::preferredRegister($slug);
            foreach (range($low - 6, $high + 6) as $pitch) {
                $name = self::NAMES[$pitch % 12];
                $registerOffset = ['organ' => 1, 'harpejji' => 1, 'upright-bass' => 2][$slug] ?? 0;
                $octave = intdiv($pitch, 12) - 1 + $registerOffset;
                if (str_contains($name, '#') && ! in_array($slug, [...$full, 'celesta', 'vibraphone'], true)) {
                    $octave--;
                }
                Storage::disk('public')->put(NoteSample::path($slug, $name, $octave), 'recording');
            }
        }
    }

    public function test_five_tonal_scales_cover_all_keys_with_exact_intervals_and_common_tonic_octaves(): void
    {
        $intervals = [
            'ionian' => ['Ionian', [0, 2, 4, 5, 7, 9, 11, 12]],
            'harmonic-major' => ['Harmonic Major', [0, 2, 4, 5, 7, 8, 11, 12]],
            'harmonic-minor' => ['Harmonic Minor', [0, 2, 3, 5, 7, 8, 11, 12]],
            'melodic-minor' => ['Melodic Minor', [0, 2, 3, 5, 7, 9, 11, 12]],
            'aeolian' => ['Aeolian', [0, 2, 3, 5, 7, 8, 10, 12]],
        ];
        $rows = TonalModeAllKeysSamples::manifest();
        $this->assertCount(60, $rows);
        $this->assertSame(array_keys(TonalModeAllKeysSamples::KEYS), array_values(array_unique(array_column($rows, 'key'))));
        $this->assertSame(array_fill(0, 5, 12), array_values(array_count_values(array_column($rows, 'answer'))));
        $tonics = [];
        foreach ($rows as $row) {
            [$answer, $up] = $intervals[substr($row['recipe'], strlen($row['key']) + 1)];
            $this->assertSame($answer, $row['answer']);
            $this->assertCount(28, $row['samples']);
            $shape = [...$up, ...array_reverse(array_slice($up, 0, -1))];
            foreach ($row['samples'] as $slug => $notes) {
                $tonics[$row['key']][$slug] ??= $notes[0];
                $this->assertSame($tonics[$row['key']][$slug], $notes[0]);
                $this->assertCount(15, $notes);
                $this->assertSame(array_map(fn (int $i): float => round($i * 0.4, 3), range(0, 14)), array_column($notes, 'offset'));
                [$low, $high] = TonalModeAllKeysSamples::preferredRegister($slug);
                $matched = false;
                foreach (range(12, 108, 12) as $octaveBase) {
                    $tonic = $octaveBase + TonalModeAllKeysSamples::KEYS[$row['key']];
                    if ($tonic < $low - 6 || $tonic + 12 > $high + 6) {
                        continue;
                    }
                    $expected = [];
                    foreach ($shape as $index => $pitch) {
                        $note = NoteSample::fromRecordedMidiPitch($tonic + $pitch, $slug);
                        if ($note === null) {
                            continue 2;
                        }
                        $expected[] = [...$note, 'offset' => round($index * 0.4, 3)];
                    }
                    $matched = $matched || $notes === $expected;
                }
                $this->assertTrue($matched, $row['recipe'].'/'.$slug);
                $this->assertSame(array_map(fn (array $n): array => ['slug' => $slug, ...$n], $notes), NoteSample::resolve(TonalModeAllKeysSamples::PREFIX.'/'.$slug.'/'.$row['recipe'].'.wav'));
            }
        }
        $this->assertSame(TonalModeScaleSamples::manifest(), array_values(array_filter($rows, fn (array $r): bool => $r['key'] === 'f')));
    }

    public function test_tonal_quizzes_are_adjacent_after_major_scales_and_preserve_the_f_only_route(): void
    {
        $expected = ['Major Scales #2', 'Tonal Modes #1', 'Tonal Modes #2'];
        $this->assertSame($expected, array_slice(QuizCatalog::subcategories('Scales'), 4, 3));
        $mobile = MobileCatalog::quizzes('Scales');
        $this->assertSame($expected, array_slice(array_column($mobile, 'name'), 4, 3));
        $this->assertSame(MobileCatalog::slug('Scales · Level 1'), $mobile[5]['slug']);
        $this->assertSame(MobileCatalog::slug('Tonal Modes #2'), $mobile[6]['slug']);
        $this->assertSame(TonalModeScaleSamples::choices(), QuizAnswerPresets::presetFor('Scales', 'Tonal Modes #2'));
        $this->assertCount(28, $mobile[6]['sounds']);
        $this->assertSame(['f' => 0], TonalModeScaleSamples::KEYS);
    }

    public function test_invalid_modes_keys_and_incomplete_banks_do_not_produce_partial_scales(): void
    {
        Storage::fake('public');
        Cache::flush();
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
        foreach (['piano/c-ionian.wav', 'piano/h-ionian.wav', 'piano/c-lydian.wav', 'kazoo/c-ionian.wav'] as $path) {
            $this->assertNull(NoteSample::resolve(TonalModeAllKeysSamples::PREFIX.'/'.$path));
        }
    }

    public function test_numbering_preserves_f_only_questions_audio_and_attempts_and_builds_only_the_new_quiz(): void
    {
        $fOnly = Quiz::factory()->create(['category' => 'Scales', 'title' => 'Tonal Modes', 'slug' => 'tonal-modes']);
        $question = $fOnly->questions()->create(['position' => 1, 'prompt' => 'Keep F audio', 'answer' => 'Ionian', 'choices' => TonalModeScaleSamples::choices()]);
        $question->audioVariants()->create(['sound' => 'Piano', 'audio_path' => TonalModeScaleSamples::PREFIX.'/piano/f-ionian.wav']);
        QuizAttempt::factory()->create(['quiz_id' => $fOnly->id, 'question_sequence' => [$question->id]]);
        $before = $fOnly->fresh()->getAttributes();
        $questionsBefore = $fOnly->questions()->with('audioVariants')->get()->toJson();
        $attemptsBefore = $fOnly->attempts()->get()->toJson();
        $other = Quiz::factory()->create(['category' => 'Scales', 'title' => 'Major Scales #2']);
        $otherBefore = $other->fresh()->getAttributes();
        $id = null;
        foreach (range(1, 2) as $run) {
            ob_start();
            try {
                require base_path('scripts/audio/build_tonal_modes_two.php');
            } finally {
                ob_end_clean();
            }
            $this->assertSame('Tonal Modes #1', $fOnly->fresh()->title);
            $this->assertSame(array_diff_key($before, ['title' => true, 'updated_at' => true]), array_diff_key($fOnly->fresh()->getAttributes(), ['title' => true, 'updated_at' => true]));
            $this->assertSame($questionsBefore, $fOnly->questions()->with('audioVariants')->get()->toJson());
            $this->assertSame($attemptsBefore, $fOnly->attempts()->get()->toJson());
            $this->assertSame($otherBefore, $other->fresh()->getAttributes());
            $quiz = Quiz::where('category', 'Scales')->where('title', 'Tonal Modes #2')->sole();
            $id ??= $quiz->id;
            $this->assertSame($id, $quiz->id);
            $this->assertTrue($quiz->is_published);
            $this->assertSame(25, $quiz->question_count);
            $this->assertSame(60, $quiz->questions()->count());
            foreach ($quiz->questions()->with('audioVariants')->get() as $q) {
                $this->assertSame(TonalModeScaleSamples::choices(), $q->choices);
                $this->assertCount(28, $q->audioVariants);
            }
        }
        $this->assertSame(3, Quiz::count());
        $this->actingAs(User::factory()->create())->post(route('quiz.start', $quiz))->assertRedirect();
        $attempt = $quiz->attempts()->firstOrFail();
        $this->assertCount(25, $attempt->question_sequence);
        $this->assertCount(25, array_unique($attempt->question_sequence));
        $answer = $quiz->questions()->findOrFail($attempt->question_sequence[0])->answer;
        $this->post(route('quiz.answer', $quiz), ['choice' => $answer])->assertRedirect();
        $this->assertSame(1, $attempt->fresh()->correct_answers);
    }
}
