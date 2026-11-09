<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\User;
use App\Support\DorianAeolianScaleSamples;
use App\Support\MobileCatalog;
use App\Support\NoteSample;
use App\Support\QuizAnswerPresets;
use App\Support\QuizCatalog;
use App\Support\TonalModeScaleSamples;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DorianAeolianScaleLiveMixTest extends TestCase
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

    public function test_both_modes_cover_all_keys_with_exact_intervals_common_registers_and_equal_timing(): void
    {
        $expected = [
            'dorian' => ['Dorian', [48, 50, 51, 53, 55, 57, 58, 60]],
            'aeolian' => ['Aeolian', [48, 50, 51, 53, 55, 56, 58, 60]],
        ];
        $this->assertSame($expected, DorianAeolianScaleSamples::voicings());
        $rows = DorianAeolianScaleSamples::manifest();
        $this->assertCount(24, $rows);
        $this->assertSame(array_keys(DorianAeolianScaleSamples::KEYS), array_values(array_unique(array_column($rows, 'key'))));
        $this->assertEqualsCanonicalizing([12, 12], array_values(array_count_values(array_column($rows, 'answer'))));
        $roots = [];
        foreach ($rows as $row) {
            $recipe = substr($row['recipe'], strlen($row['key']) + 1);
            [$answer, $up] = $expected[$recipe];
            $this->assertSame($answer, $row['answer']);
            $this->assertCount(28, $row['samples']);
            $shape = [...$up, ...array_reverse(array_slice($up, 0, -1))];
            foreach ($row['samples'] as $slug => $notes) {
                $this->assertCount(15, $notes);
                $roots[$row['key']][$slug] ??= $notes[0];
                $this->assertSame($roots[$row['key']][$slug], $notes[0]);
                $this->assertSame(array_map(fn (int $i): float => round($i * 0.4, 3), range(0, 14)), array_column($notes, 'offset'));
                [$low, $high] = DorianAeolianScaleSamples::preferredRegister($slug);
                $matched = false;
                foreach (range(-48, 48, 12) as $shift) {
                    $transpose = DorianAeolianScaleSamples::KEYS[$row['key']] + $shift;
                    if (min($shape) + $transpose < $low - 6 || max($shape) + $transpose > $high + 6) {
                        continue;
                    }
                    $resolved = [];
                    foreach ($shape as $i => $pitch) {
                        $note = NoteSample::fromRecordedMidiPitch($pitch + $transpose, $slug);
                        if ($note === null) {
                            continue 2;
                        }
                        $resolved[] = [...$note, 'offset' => round($i * 0.4, 3)];
                    }
                    $matched = $matched || $resolved === $notes;
                }
                $this->assertTrue($matched, $row['recipe'].'/'.$slug);
                $this->assertSame(array_map(fn (array $n): array => ['slug' => $slug, ...$n], $notes), NoteSample::resolve(DorianAeolianScaleSamples::PREFIX.'/'.$slug.'/'.$row['recipe'].'.wav'));
            }
        }
    }

    public function test_new_quiz_is_second_in_both_catalogs_and_keeps_tonal_modes_as_a_separate_lesson(): void
    {
        $this->assertSame('Ionian and Lydian', QuizCatalog::subcategories('Scales')[0]);
        $this->assertSame('Dorian and Aeolian', QuizCatalog::subcategories('Scales')[1]);
        $this->assertSame('Tonal Modes #1', QuizCatalog::subcategories('Scales')[5]);
        $this->assertSame(['Ionian and Lydian', 'Dorian and Aeolian', 'Phrygian and Locrian', 'Major Scales #1', 'Major Scales #2', 'Tonal Modes #1'], array_slice(array_column(MobileCatalog::quizzes('Scales'), 'name'), 0, 6));
        $this->assertSame(['Dorian', 'Aeolian'], QuizAnswerPresets::presetFor('Scales', 'Dorian and Aeolian'));
        $this->assertCount(28, QuizCatalog::soundsFor('Scales', 'Dorian and Aeolian'));
        $this->assertCount(28, MobileCatalog::find(MobileCatalog::slug('Dorian and Aeolian'))['sounds']);
        $this->assertSame(TonalModeScaleSamples::choices(), QuizAnswerPresets::presetFor('Scales', 'Tonal Modes #1'));
    }

    public function test_invalid_modes_keys_and_incomplete_recordings_do_not_play_partial_scales(): void
    {
        Storage::fake('public');
        Cache::flush();
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
        foreach (['piano/c-dorian.wav', 'piano/h-aeolian.wav', 'piano/f-ionian.wav', 'kazoo/f-aeolian.wav'] as $path) {
            $this->assertNull(NoteSample::resolve(DorianAeolianScaleSamples::PREFIX.'/'.$path));
        }
    }

    public function test_builder_creates_only_the_new_quiz_preserves_existing_lessons_and_grades_a_balanced_session(): void
    {
        $before = [];
        foreach ([['Scales', 'Tonal Modes #1'], ['Scales', 'Ionian and Lydian'], ['Scales', 'Dorian Mode'], ['Modal Voicings', 'Lydian Mode #1'], ['Modal Harmony', 'Dorian and Aeolian']] as [$category, $title]) {
            $quiz = Quiz::factory()->create(compact('category', 'title'));
            $quiz->questions()->create(['position' => 1, 'prompt' => 'Keep', 'answer' => 'Keep', 'choices' => ['Keep']]);
            $before[$quiz->id] = $quiz->fresh()->load('questions')->toJson();
        }
        $id = null;
        foreach (range(1, 2) as $run) {
            ob_start();
            try {
                require base_path('scripts/audio/build_dorian_aeolian_scales.php');
            } finally {
                ob_end_clean();
            }
            $quiz = Quiz::where('category', 'Scales')->where('title', 'Dorian and Aeolian')->sole();
            $id ??= $quiz->id;
            $this->assertSame($id, $quiz->id);
            $this->assertTrue($quiz->is_published);
            $this->assertSame('Intermediate', $quiz->difficulty);
            $this->assertSame(25, $quiz->question_count);
            $this->assertSame(24, $quiz->questions()->count());
            foreach ($quiz->questions()->with('audioVariants')->get() as $question) {
                $this->assertSame(['Dorian', 'Aeolian'], $question->choices);
                $this->assertCount(28, $question->audioVariants);
            }
            foreach ($before as $otherId => $snapshot) {
                $this->assertSame($snapshot, Quiz::findOrFail($otherId)->load('questions')->toJson());
            }
        }
        $this->actingAs(User::factory()->create())->post(route('quiz.start', $quiz))->assertRedirect();
        $attempt = $quiz->attempts()->firstOrFail();
        $this->assertCount(25, $attempt->question_sequence);
        $this->assertCount(24, array_unique($attempt->question_sequence));
        $answers = $quiz->questions()->get()->keyBy('id');
        $this->assertEqualsCanonicalizing([12, 13], array_values(array_count_values(array_map(fn (int $id): string => $answers[$id]->answer, $attempt->question_sequence))));
        $this->post(route('quiz.answer', $quiz), ['choice' => $answers[$attempt->question_sequence[0]]->answer])->assertRedirect();
        $this->assertSame(1, $attempt->fresh()->correct_answers);
    }
}
