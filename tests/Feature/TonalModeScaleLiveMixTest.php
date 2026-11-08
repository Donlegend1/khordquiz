<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Support\MobileCatalog;
use App\Support\NoteSample;
use App\Support\QuizAnswerPresets;
use App\Support\QuizCatalog;
use App\Support\TonalModeScaleSamples;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TonalModeScaleLiveMixTest extends TestCase
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

    public function test_five_exact_f_scales_play_one_octave_up_and_down_for_all_instruments(): void
    {
        $ascending = [
            'ionian' => ['Ionian', [53, 55, 57, 58, 60, 62, 64, 65]],
            'harmonic-major' => ['Harmonic Major', [53, 55, 57, 58, 60, 61, 64, 65]],
            'harmonic-minor' => ['Harmonic Minor', [53, 55, 56, 58, 60, 61, 64, 65]],
            'melodic-minor' => ['Melodic Minor', [53, 55, 56, 58, 60, 62, 64, 65]],
            'aeolian' => ['Aeolian', [53, 55, 56, 58, 60, 61, 63, 65]],
        ];
        $this->assertSame($ascending, TonalModeScaleSamples::voicings());
        $manifest = TonalModeScaleSamples::manifest();
        $this->assertCount(5, $manifest);
        $this->assertSame(array_fill(0, 5, 'f'), array_column($manifest, 'key'));
        $bass = [];
        foreach ($manifest as $row) {
            [$answer, $up] = $ascending[substr($row['recipe'], 2)];
            $this->assertSame($answer, $row['answer']);
            $this->assertCount(28, $row['samples']);
            $shape = [...$up, ...array_reverse(array_slice($up, 0, -1))];
            foreach ($row['samples'] as $slug => $notes) {
                $this->assertCount(15, $notes);
                $this->assertSame(array_map(fn (int $i): float => round($i * 0.4, 3), range(0, 14)), array_column($notes, 'offset'));
                $this->assertSame('Fa', $notes[0]['degree']);
                $bass[$slug] ??= $notes[0];
                $this->assertSame($bass[$slug], $notes[0]);
                $this->assertSame($notes[0]['degree'], $notes[14]['degree']);
                $this->assertSame($notes[0]['octave'], $notes[14]['octave']);
                $this->assertSame($notes[0]['octave'] + 1, $notes[7]['octave']);
                [$low, $high] = TonalModeScaleSamples::preferredRegister($slug);
                $matched = false;
                foreach (range(-48, 48, 12) as $shift) {
                    if ($low - 6 > $up[0] + $shift || $high + 6 < $up[7] + $shift) {
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
                $this->assertTrue($matched, $slug.'/'.$row['recipe']);
                $this->assertSame(array_map(fn (array $note): array => ['slug' => $slug, ...$note], $notes), NoteSample::resolve(TonalModeScaleSamples::PREFIX.'/'.$slug.'/'.$row['recipe'].'.wav'));
            }
        }
        $this->assertSame(['degree' => 'Fa', 'octave' => 3, 'offset' => 0.0], $bass['piano']);
    }

    public function test_catalogs_offer_the_five_existing_choices_and_all_twenty_eight_instruments_only_for_tonal_modes(): void
    {
        $this->assertSame(['Ionian', 'Harmonic Major', 'Harmonic Minor', 'Melodic Minor', 'Aeolian'], TonalModeScaleSamples::choices());
        $this->assertSame(TonalModeScaleSamples::choices(), QuizAnswerPresets::presetFor('Scales', 'Tonal Modes #1'));
        $this->assertSame(QuizCatalog::allInstruments(), QuizCatalog::soundsFor('Scales', 'Tonal Modes #1'));
        $this->assertCount(28, MobileCatalog::find(MobileCatalog::slug(TonalModeScaleSamples::MOBILE_TITLE))['sounds']);
        $this->assertSame('Tonal Modes #1', MobileCatalog::quizzes('Scales')[5]['name']);
        $this->assertCount(21, MobileCatalog::quizzes('Scales'));
        $this->assertNotSame(TonalModeScaleSamples::choices(), QuizAnswerPresets::presetFor('Scales', 'Dorian Mode'));
        $this->assertNotSame(TonalModeScaleSamples::sounds(), QuizCatalog::soundsFor('Scales', 'Dorian Mode'));
    }

    public function test_missing_recordings_invalid_scales_and_other_keys_never_play_partial_or_wrong_scales(): void
    {
        Storage::fake('public');
        Cache::flush();
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
        Storage::disk('public')->put(NoteSample::path('piano', 'Fa', 3), 'tonic');
        foreach (['piano/f-ionian.wav', 'kazoo/f-ionian.wav', 'piano/g-ionian.wav', 'piano/f-dorian.wav'] as $path) {
            $this->assertNull(NoteSample::resolve(TonalModeScaleSamples::PREFIX.'/'.$path));
        }
    }

    public function test_rebuild_preserves_quiz_identity_other_lessons_and_grades_the_new_balanced_pool(): void
    {
        $quiz = Quiz::factory()->create(['category' => 'Scales', 'title' => 'Tonal Modes', 'slug' => 'tonal-modes']);
        $old = $quiz->questions()->create(['position' => 1, 'prompt' => 'Old text example', 'answer' => 'Dorian', 'choices' => ['Dorian']]);
        $oldAttempt = QuizAttempt::factory()->create(['quiz_id' => $quiz->id, 'question_sequence' => [$old->id]]);
        $others = [];
        foreach ([['Scales', 'Dorian Mode'], ['Modal Voicings', 'Dorian Mode #1'], ['Modal Harmony', 'Modal Harmony #8'], ['Modal Harmony', 'Tonal Modes']] as [$category, $title]) {
            $other = Quiz::factory()->create(compact('category', 'title'));
            $other->questions()->create(['position' => 1, 'prompt' => 'Keep', 'answer' => 'Keep', 'choices' => ['Keep']]);
            $others[$other->id] = $other->fresh()->load('questions')->toJson();
        }
        foreach (range(1, 2) as $run) {
            ob_start();
            try {
                require base_path('scripts/audio/build_tonal_mode_scales.php');
            } finally {
                ob_end_clean();
            }
            $this->assertSame('tonal-modes', $quiz->fresh()->slug);
            $this->assertSame(5, $quiz->questions()->count());
            $this->assertSame(25, $quiz->fresh()->question_count);
            $this->assertTrue($quiz->fresh()->is_published);
            foreach ($quiz->questions()->with('audioVariants')->get() as $question) {
                $this->assertSame(TonalModeScaleSamples::choices(), $question->choices);
                $this->assertContains($question->answer, TonalModeScaleSamples::choices());
                $this->assertCount(28, $question->audioVariants);
            }
            foreach ($others as $id => $before) {
                $this->assertSame($before, Quiz::findOrFail($id)->load('questions')->toJson());
            }
        }
        $this->assertDatabaseMissing('quiz_questions', ['id' => $old->id]);
        $this->assertDatabaseMissing('quiz_attempts', ['id' => $oldAttempt->id]);
        $this->assertSame(1, Quiz::where('category', 'Scales')->where('title', 'Tonal Modes #1')->count());
        $this->actingAs(User::factory()->create())->post(route('quiz.start', $quiz))->assertRedirect();
        $attempt = $quiz->attempts()->firstOrFail();
        $this->assertCount(25, $attempt->question_sequence);
        $this->assertSame([5, 5, 5, 5, 5], array_values(array_count_values($attempt->question_sequence)));
        $answer = $quiz->questions()->findOrFail($attempt->question_sequence[0])->answer;
        $this->post(route('quiz.answer', $quiz), ['choice' => $answer])->assertRedirect();
        $this->assertSame(1, $attempt->fresh()->correct_answers);
    }
}
