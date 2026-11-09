<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Support\IonianScaleAllKeysSamples;
use App\Support\IonianScaleSamples;
use App\Support\IonianVoicingSamples;
use App\Support\MobileCatalog;
use App\Support\NoteSample;
use App\Support\QuizAnswerPresets;
use App\Support\QuizCatalog;
use App\Support\TonalModeScaleSamples;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IonianScaleLiveMixTest extends TestCase
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

    public function test_six_scales_use_exactly_the_modes_from_the_ionian_modal_voicings(): void
    {
        $expected = [
            'ionian' => ['Ionian', [0, 2, 4, 5, 7, 9, 11]],
            'ionian-flat-two' => ['Ionian ♭2', [0, 1, 4, 5, 7, 9, 11]],
            'ionian-sharp-two' => ['Ionian ♯2', [0, 3, 4, 5, 7, 9, 11]],
            'ionian-augmented' => ['Ionian Augmented', [0, 2, 4, 5, 8, 9, 11]],
            'ionian-flat-six' => ['Ionian ♭6', [0, 2, 4, 5, 7, 8, 11]],
            'ionian-flat-two-flat-six' => ['Ionian ♭2 ♭6', [0, 1, 4, 5, 7, 8, 11]],
        ];
        $this->assertSame($expected, IonianScaleSamples::SCALES);
        $this->assertSame(IonianVoicingSamples::choices(), IonianScaleSamples::choices());
        $this->assertSame(IonianVoicingSamples::choices(), IonianScaleAllKeysSamples::choices());
        foreach ($expected as $id => [$answer, $intervals]) {
            $this->assertSame($answer, IonianVoicingSamples::VOICINGS[$id][0]);
            $this->assertEqualsCanonicalizing($intervals, [...array_map(fn (int $p): int => $p % 12, IonianVoicingSamples::VOICINGS[$id][1]), 5]);
            $this->assertContains(4, $intervals);
            $this->assertContains(11, $intervals);
        }
    }

    public function test_both_lessons_have_correct_key_coverage_and_match_each_other_in_c(): void
    {
        foreach ([IonianScaleSamples::class, IonianScaleAllKeysSamples::class] as $source) {
            $rows = $source::manifest();
            $this->assertCount($source === IonianScaleSamples::class ? 6 : 72, $rows);
            $this->assertSame(array_keys($source::KEYS), array_values(array_unique(array_column($rows, 'key'))));
            $roots = [];
            foreach ($rows as $row) {
                [$answer, $intervals] = IonianScaleSamples::SCALES[substr($row['recipe'], strlen($row['key']) + 1)];
                $this->assertSame($answer, $row['answer']);
                $this->assertCount(28, $row['samples']);
                $up = [...$intervals, 12];
                $shape = [...$up, ...array_reverse(array_slice($up, 0, -1))];
                foreach ($row['samples'] as $slug => $notes) {
                    $this->assertCount(15, $notes);
                    $roots[$row['key']][$slug] ??= $notes[0];
                    $this->assertSame($roots[$row['key']][$slug], $notes[0]);
                    $this->assertSame(array_map(fn (int $i): float => round($i * 0.4, 3), range(0, 14)), array_column($notes, 'offset'));
                    [$low, $high] = $source::preferredRegister($slug);
                    $matched = false;
                    foreach (range(12, 108, 12) as $base) {
                        $tonic = $base + ($source === IonianScaleSamples::class ? 0 : $source::KEYS[$row['key']]);
                        if ($tonic < $low - 6 || $tonic + 12 > $high + 6) {
                            continue;
                        }
                        $expected = [];
                        foreach ($shape as $i => $pitch) {
                            $note = NoteSample::fromRecordedMidiPitch($tonic + $pitch, $slug);
                            if ($note === null) {
                                continue 2;
                            }
                            $expected[] = [...$note, 'offset' => round($i * 0.4, 3)];
                        }
                        $matched = $matched || $expected === $notes;
                    }
                    $this->assertTrue($matched, $source::TITLE.'/'.$row['recipe'].'/'.$slug);
                    $this->assertSame(array_map(fn (array $n): array => ['slug' => $slug, ...$n], $notes), NoteSample::resolve($source::PREFIX.'/'.$slug.'/'.$row['recipe'].'.wav'));
                }
            }
        }
        $this->assertSame(IonianScaleSamples::manifest(), array_values(array_filter(IonianScaleAllKeysSamples::manifest(), fn (array $r): bool => $r['key'] === 'c')));
        Storage::fake('public');
        Cache::flush();
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
        foreach (['piano/c-ionian.wav', 'piano/d-ionian.wav', 'kazoo/c-ionian.wav', 'piano/c-ionian-unknown.wav'] as $path) {
            $this->assertNull(NoteSample::resolve(IonianScaleSamples::PREFIX.'/'.$path));
        }
    }

    public function test_catalogs_show_the_pair_after_locrian_scales(): void
    {
        $expected = ['Locrian Scales #2', 'Ionian Scales #1', 'Ionian Scales #2'];
        $this->assertSame($expected, array_slice(QuizCatalog::subcategories('Scales'), 18, 3));
        $mobile = MobileCatalog::quizzes('Scales');
        $this->assertSame($expected, array_slice(array_column($mobile, 'name'), 18, 3));
        $this->assertSame(MobileCatalog::slug(IonianScaleSamples::TITLE), $mobile[19]['slug']);
        foreach (['Ionian Scales #1', 'Ionian Scales #2'] as $title) {
            $this->assertSame(IonianVoicingSamples::choices(), QuizAnswerPresets::presetFor('Scales', $title));
            $this->assertCount(28, QuizCatalog::soundsFor('Scales', $title));
        }
    }

    public function test_builder_reuses_the_existing_scale_quiz_and_preserves_the_modal_voicings_and_other_lessons(): void
    {
        $oldQuiz = Quiz::factory()->create(['category' => 'Scales', 'title' => 'Ionian Mode', 'slug' => 'ionian-mode']);
        $old = $oldQuiz->questions()->create(['position' => 1, 'prompt' => 'Old variants', 'answer' => 'Old label', 'choices' => ['Old label']]);
        $oldAttempt = QuizAttempt::factory()->create(['quiz_id' => $oldQuiz->id, 'question_sequence' => [$old->id]]);
        $others = [];
        foreach ([['Modal Voicings', 'Ionian Mode #1'], ['Modal Voicings', 'Ionian Mode #2'], ['Scales', 'Tonal Modes #1'], ['Scales', 'Tonal Modes #2'], ['Scales', 'Major Scales #2'], ['Scales', 'Dorian Scales #1'], ['Scales', 'Dorian Scales #2'], ['Scales', 'Lydian Scales #1'], ['Scales', 'Lydian Scales #2'], ['Scales', 'Phrygian Scales #1'], ['Scales', 'Phrygian Scales #2'], ['Scales', 'Mixolydian Scales #1'], ['Scales', 'Mixolydian Scales #2'], ['Scales', 'Aeolian Scales #1'], ['Scales', 'Aeolian Scales #2'], ['Scales', 'Locrian Scales #1'], ['Scales', 'Locrian Scales #2']] as [$category, $title]) {
            $other = Quiz::factory()->create(compact('category', 'title'));
            $other->questions()->create(['position' => 1, 'prompt' => 'Keep', 'answer' => 'Keep', 'choices' => ['Keep']]);
            $others[$other->id] = $other->fresh()->load('questions')->toJson();
        }
        $ids = null;
        foreach (range(1, 2) as $run) {
            ob_start();
            try {
                require base_path('scripts/audio/build_ionian_scales.php');
            } finally {
                ob_end_clean();
            }
            $pair = Quiz::where('category', 'Scales')->whereIn('title', ['Ionian Scales #1', 'Ionian Scales #2'])->orderBy('id')->get();
            $this->assertCount(2, $pair);
            $ids ??= $pair->modelKeys();
            $this->assertSame($ids, $pair->modelKeys());
            $this->assertSame('Ionian Scales #1', $oldQuiz->fresh()->title);
            $this->assertSame('ionian-mode', $oldQuiz->fresh()->slug);
            foreach ([IonianScaleSamples::class, IonianScaleAllKeysSamples::class] as $source) {
                $quiz = $pair->firstWhere('title', $source::TITLE);
                $this->assertTrue($quiz->is_published);
                $this->assertSame(25, $quiz->question_count);
                $this->assertSame(count($source::KEYS) * 6, $quiz->questions()->count());
                foreach ($quiz->questions()->with('audioVariants')->get() as $question) {
                    $this->assertSame(IonianVoicingSamples::choices(), $question->choices);
                    $this->assertCount(28, $question->audioVariants);
                }
            }
            foreach ($others as $id => $snapshot) {
                $this->assertSame($snapshot, Quiz::findOrFail($id)->load('questions')->toJson());
            }
        }
        $this->assertDatabaseMissing('quiz_questions', ['id' => $old->id]);
        $this->assertDatabaseMissing('quiz_attempts', ['id' => $oldAttempt->id]);
        foreach ($pair as $quiz) {
            $this->actingAs(User::factory()->create())->post(route('quiz.start', $quiz))->assertRedirect();
            $attempt = $quiz->attempts()->firstOrFail();
            $this->assertCount(25, $attempt->question_sequence);
            $answer = $quiz->questions()->findOrFail($attempt->question_sequence[0])->answer;
            $this->post(route('quiz.answer', $quiz), ['choice' => $answer])->assertRedirect();
            $this->assertSame(1, $attempt->fresh()->correct_answers);
        }
    }
}
