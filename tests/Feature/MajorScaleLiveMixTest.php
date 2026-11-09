<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Support\MajorScaleSamples;
use App\Support\MobileCatalog;
use App\Support\NoteSample;
use App\Support\QuizAnswerPresets;
use App\Support\QuizCatalog;
use App\Support\TonalModeScaleSamples;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MajorScaleLiveMixTest extends TestCase
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

    public function test_seven_modes_start_on_c_and_play_the_correct_octave_up_and_down(): void
    {
        $ascending = [
            'ionian' => ['Ionian', [48, 50, 52, 53, 55, 57, 59, 60]],
            'dorian' => ['Dorian', [48, 50, 51, 53, 55, 57, 58, 60]],
            'phrygian' => ['Phrygian', [48, 49, 51, 53, 55, 56, 58, 60]],
            'lydian' => ['Lydian', [48, 50, 52, 54, 55, 57, 59, 60]],
            'mixolydian' => ['Mixolydian', [48, 50, 52, 53, 55, 57, 58, 60]],
            'aeolian' => ['Aeolian', [48, 50, 51, 53, 55, 56, 58, 60]],
            'locrian' => ['Locrian', [48, 49, 51, 53, 54, 56, 58, 60]],
        ];
        $this->assertSame($ascending, MajorScaleSamples::voicings());
        $rows = MajorScaleSamples::manifest();
        $this->assertCount(7, $rows);
        $this->assertSame(array_fill(0, 7, 'c'), array_column($rows, 'key'));
        $roots = [];
        foreach ($rows as $row) {
            [$answer, $up] = $ascending[substr($row['recipe'], 2)];
            $this->assertSame($answer, $row['answer']);
            $this->assertCount(28, $row['samples']);
            $shape = [...$up, ...array_reverse(array_slice($up, 0, -1))];
            foreach ($row['samples'] as $slug => $notes) {
                $this->assertCount(15, $notes);
                $roots[$slug] ??= $notes[0];
                $this->assertSame($roots[$slug], $notes[0]);
                $this->assertSame('Do', $notes[0]['degree']);
                $this->assertSame($notes[0]['octave'] + 1, $notes[7]['octave']);
                $this->assertSame($notes[0]['octave'], $notes[14]['octave']);
                $this->assertSame(array_map(fn (int $i): float => round($i * 0.4, 3), range(0, 14)), array_column($notes, 'offset'));
                [$low, $high] = MajorScaleSamples::preferredRegister($slug);
                $matched = false;
                foreach (range(-48, 48, 12) as $shift) {
                    if (min($shape) + $shift < $low - 6 || max($shape) + $shift > $high + 6) {
                        continue;
                    }
                    $expected = [];
                    foreach ($shape as $index => $pitch) {
                        $note = NoteSample::fromRecordedMidiPitch($pitch + $shift, $slug);
                        if ($note === null) {
                            continue 2;
                        }
                        $expected[] = [...$note, 'offset' => round($index * 0.4, 3)];
                    }
                    $matched = $matched || $notes === $expected;
                }
                $this->assertTrue($matched, $row['recipe'].'/'.$slug);
                $this->assertSame(array_map(fn (array $n): array => ['slug' => $slug, ...$n], $notes), NoteSample::resolve(MajorScaleSamples::PREFIX.'/'.$slug.'/'.$row['recipe'].'.wav'));
            }
        }
    }

    public function test_renamed_quiz_is_before_tonal_modes_and_preserves_the_mobile_route(): void
    {
        $this->assertSame(['Major Scales #1', 'Major Scales #2', 'Tonal Modes #1'], array_slice(QuizCatalog::subcategories('Scales'), 3, 3));
        $mobile = MobileCatalog::quizzes('Scales');
        $this->assertSame(['Major Scales #1', 'Major Scales #2', 'Tonal Modes #1'], array_slice(array_column($mobile, 'name'), 3, 3));
        $this->assertSame(MobileCatalog::slug('Scales · Level 3'), $mobile[3]['slug']);
        $this->assertCount(28, $mobile[3]['sounds']);
        $this->assertSame(['Ionian', 'Dorian', 'Phrygian', 'Lydian', 'Mixolydian', 'Aeolian', 'Locrian'], QuizAnswerPresets::presetFor('Scales', 'Major Scales #1'));
        $this->assertNotContains('Major mode', QuizCatalog::subcategories('Scales'));
        $this->assertSame(TonalModeScaleSamples::choices(), QuizAnswerPresets::presetFor('Scales', 'Tonal Modes #1'));
    }

    public function test_other_keys_and_missing_banks_do_not_play_partial_scales(): void
    {
        Storage::fake('public');
        Cache::flush();
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
        foreach (['piano/c-ionian.wav', 'piano/d-ionian.wav', 'piano/c-harmonic-major.wav', 'kazoo/c-ionian.wav'] as $path) {
            $this->assertNull(NoteSample::resolve(MajorScaleSamples::PREFIX.'/'.$path));
        }
    }

    public function test_rebuild_renames_existing_quiz_keeps_identifiers_and_preserves_other_quizzes(): void
    {
        $quiz = Quiz::factory()->create(['category' => 'Scales', 'title' => 'Major mode', 'slug' => 'major-mode']);
        $old = $quiz->questions()->create(['position' => 1, 'prompt' => 'Old example', 'answer' => 'Ionian', 'choices' => ['Ionian']]);
        $oldAttempt = QuizAttempt::factory()->create(['quiz_id' => $quiz->id, 'question_sequence' => [$old->id]]);
        $others = [];
        foreach ([['Scales', 'Tonal Modes #1'], ['Scales', 'Ionian and Lydian'], ['Scales', 'Dorian and Aeolian'], ['Scales', 'Phrygian and Locrian'], ['Modal Voicings', 'Major Modes #1'], ['Modal Harmony', 'Major Scales #1']] as [$category, $title]) {
            $other = Quiz::factory()->create(compact('category', 'title'));
            $other->questions()->create(['position' => 1, 'prompt' => 'Keep', 'answer' => 'Keep', 'choices' => ['Keep']]);
            $others[$other->id] = $other->fresh()->load('questions')->toJson();
        }
        foreach (range(1, 2) as $run) {
            ob_start();
            try {
                require base_path('scripts/audio/build_major_scales.php');
            } finally {
                ob_end_clean();
            }
            $this->assertSame($quiz->id, Quiz::where('category', 'Scales')->where('title', 'Major Scales #1')->sole()->id);
            $this->assertSame('major-mode', $quiz->fresh()->slug);
            $this->assertTrue($quiz->fresh()->is_published);
            $this->assertSame(25, $quiz->fresh()->question_count);
            $this->assertSame(7, $quiz->questions()->count());
            foreach ($quiz->questions()->with('audioVariants')->get() as $question) {
                $this->assertSame(MajorScaleSamples::choices(), $question->choices);
                $this->assertCount(28, $question->audioVariants);
            }
            foreach ($others as $id => $snapshot) {
                $this->assertSame($snapshot, Quiz::findOrFail($id)->load('questions')->toJson());
            }
        }
        $this->assertDatabaseMissing('quiz_questions', ['id' => $old->id]);
        $this->assertDatabaseMissing('quiz_attempts', ['id' => $oldAttempt->id]);
        $this->actingAs(User::factory()->create())->post(route('quiz.start', $quiz))->assertRedirect();
        $attempt = $quiz->attempts()->firstOrFail();
        $this->assertCount(25, $attempt->question_sequence);
        $this->assertEqualsCanonicalizing([3, 3, 3, 4, 4, 4, 4], array_values(array_count_values($attempt->question_sequence)));
        $answer = $quiz->questions()->findOrFail($attempt->question_sequence[0])->answer;
        $this->post(route('quiz.answer', $quiz), ['choice' => $answer])->assertRedirect();
        $this->assertSame(1, $attempt->fresh()->correct_answers);
    }
}
