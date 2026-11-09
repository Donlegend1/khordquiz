<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Support\MobileCatalog;
use App\Support\ModalHarmonySamples;
use App\Support\ModalVoicingLessons;
use App\Support\NoteSample;
use App\Support\QuizAnswerPresets;
use App\Support\QuizCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ModalHarmonyLiveMixTest extends TestCase
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
            $register = ModalHarmonySamples::preferredRegister($slug);
            if ($register === null) {
                continue;
            }
            foreach (range($register[0] - 6, $register[1] + 6) as $pitch) {
                $name = self::NAMES[$pitch % 12];
                $octave = intdiv($pitch, 12) - 1 + (in_array($slug, ['organ', 'harpejji'], true) ? 1 : 0);
                if (str_contains($name, '#') && ! in_array($slug, [...$full, 'celesta', 'vibraphone'], true)) {
                    $octave--;
                }
                Storage::disk('public')->put(NoteSample::path($slug, $name, $octave), 'recording');
            }
        }
    }

    public function test_agreed_groups_have_exactly_two_inversions_of_each_upper_major_triad(): void
    {
        $expected = [
            1 => [
                'flat-two-major' => ['Phrygian · ♭II Major', [[48, 53, 56, 61], [48, 56, 61, 65]]],
                'two-major' => ['Lydian Dominant · II Major', [[48, 54, 57, 62], [48, 57, 62, 66]]],
                'flat-three-major' => ['Aeolian · ♭III Major', [[48, 55, 58, 63], [48, 58, 63, 67]]],
                'three-major' => ['Lydian Augmented · III Major', [[48, 56, 59, 64], [48, 59, 64, 68]]],
                'five-major' => ['Ionian · V Major', [[48, 50, 55, 59], [48, 55, 59, 62]]],
            ],
            2 => [
                'four-major' => ['Ionian · IV Major', [[48, 53, 57, 60], [48, 57, 60, 65]]],
                'flat-five-major' => ['Altered Dominant · ♭V Major', [[48, 54, 58, 61], [48, 58, 61, 66]]],
                'six-major' => ['Dominant Diminished · VI Major', [[48, 52, 57, 61], [48, 57, 61, 64]]],
                'flat-seven-major' => ['Mixolydian · ♭VII Major', [[48, 53, 58, 62], [48, 58, 62, 65]]],
                'seven-major' => ['Lydian Diminished · VII Major', [[48, 54, 59, 63], [48, 51, 54, 59]]],
            ],
        ];
        $roots = [1 => [1, 2, 3, 4, 7], 2 => [5, 6, 9, 10, 11]];
        $this->assertSame($expected, array_intersect_key(ModalHarmonySamples::GROUPS, $expected));
        foreach ($expected as $group => $options) {
            $this->assertCount(5, $options);
            foreach (array_values($options) as $index => [$answer, $shapes]) {
                $this->assertCount(2, $shapes);
                $this->assertNotSame($shapes[0], $shapes[1]);
                foreach ($shapes as $shape) {
                    $this->assertSame(48, $shape[0]);
                    $this->assertCount(4, $shape);
                    $upper = array_map(fn (int $p): int => ($p - $roots[$group][$index] + 12) % 12, array_slice($shape, 1));
                    $this->assertEqualsCanonicalizing([0, 4, 7], $upper);
                }
            }
        }
    }

    public function test_every_lesson_has_complete_key_and_inversion_coverage_in_a_common_register(): void
    {
        foreach (range(1, 8) as $number) {
            $title = ModalHarmonySamples::titles()[$number - 1];
            $rows = ModalHarmonySamples::manifest($number);
            $group = ModalHarmonySamples::group($number);
            $this->assertCount(count(ModalHarmonySamples::choices($number)) * 2 * ($number % 2 === 1 ? 1 : 12), $rows);
            $this->assertSame($number % 2 === 1 ? ['c'] : array_keys(ModalVoicingLessons::KEYS), array_values(array_unique(array_column($rows, 'key'))));
            $this->assertSame(ModalHarmonySamples::choices($number), QuizAnswerPresets::presetFor('Modal Harmony', $title));
            $this->assertSame(ModalHarmonySamples::sounds($number), MobileCatalog::sounds(MobileCatalog::slug($title), 'Modal Harmony'));
            $this->assertSame(array_fill(0, count(ModalHarmonySamples::choices($number)), $number % 2 === 1 ? 2 : 24), array_values(array_count_values(array_column($rows, 'answer'))));
            $bass = [];
            foreach ($rows as $row) {
                $this->assertCount(count(ModalHarmonySamples::sounds($number)), $row['samples']);
                [$answer, $shape] = ModalHarmonySamples::voicings($group)[substr($row['recipe'], strlen($row['key']) + 1)];
                $this->assertSame($answer, $row['answer']);
                foreach ($row['samples'] as $slug => $notes) {
                    $bass[$row['key']][$slug] ??= $notes[0];
                    $this->assertSame($bass[$row['key']][$slug], $notes[0]);
                    $this->assertCount(4, $notes);
                    $this->assertSame([0.0, 0.0, 0.0, 0.0], array_column($notes, 'offset'));
                    [$low, $high] = ModalHarmonySamples::preferredRegister($slug);
                    $matched = false;
                    foreach (range(-48, 48, 12) as $shift) {
                        $pitches = array_map(fn (int $p): int => $p + ModalVoicingLessons::KEYS[$row['key']] + $shift, $shape);
                        if (min($pitches) < $low - 6 || max($pitches) > $high + 6) {
                            continue;
                        }
                        $matched = $matched || $notes === array_map(fn (int $p): ?array => NoteSample::fromRecordedMidiPitch($p, $slug), $pitches);
                    }
                    $this->assertTrue($matched, $title.'/'.$slug.'/'.$row['recipe']);
                    $this->assertSame(array_map(fn (array $n): array => ['slug' => $slug, ...$n], $notes), NoteSample::resolve(ModalHarmonySamples::prefix($number).'/'.$group.'/'.$slug.'/'.$row['recipe'].'.wav'));
                }
            }
            $this->assertSame(['degree' => 'Do', 'octave' => 3, 'offset' => 0.0], $bass['c']['piano']);
        }
        foreach ([1, 3, 5, 7] as $number) {
            $allC = array_values(array_filter(ModalHarmonySamples::manifest($number + 1), fn (array $r): bool => $r['key'] === 'c'));
            foreach ($allC as $i => $row) {
                $single = ModalHarmonySamples::manifest($number)[$i];
                foreach ($row['samples'] as $slug => $notes) {
                    $this->assertSame($single['samples'][$slug], $notes);
                }
            }
        }
    }

    public function test_missing_samples_and_invalid_paths_do_not_produce_partial_chords(): void
    {
        Storage::fake('public');
        Cache::flush();
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
        Storage::disk('public')->put(NoteSample::path('piano', 'Do', 3), 'bass');
        foreach (['1/piano/c-flat-two-major-v1.wav', '1/tuba/c-flat-two-major-v1.wav', '3/piano/c-flat-two-major-v1.wav', '1/piano/h-flat-two-major-v1.wav', '1/piano/c-flat-two-major-v3.wav', '1/piano/c-flat-six-major-v1.wav'] as $path) {
            $this->assertNull(NoteSample::resolve(ModalHarmonySamples::PREFIX.'/'.$path));
        }
    }

    public function test_builder_reuses_draft_publishes_eight_quizzes_grades_answers_and_preserves_other_quizzes(): void
    {
        $draft = Quiz::factory()->create(['category' => 'Modal Harmony', 'title' => 'Major Triads over Bass', 'is_published' => false]);
        $other = Quiz::factory()->create(['category' => 'Modal Voicings', 'title' => 'Lydian Mode #1']);
        $other->questions()->create(['position' => 1, 'prompt' => 'Keep', 'answer' => 'Keep', 'choices' => ['Keep']]);
        $before = $other->fresh()->load('questions')->toJson();
        ob_start();
        try {
            require base_path('scripts/audio/build_modal_harmony.php');
        } finally {
            ob_end_clean();
        }
        $this->assertSame(8, Quiz::where('category', 'Modal Harmony')->count());
        $this->assertSame('Modal Harmony #1', $draft->fresh()->title);
        $this->assertSame($before, $other->fresh()->load('questions')->toJson());
        foreach (ModalHarmonySamples::titles() as $index => $title) {
            $number = $index + 1;
            $quiz = Quiz::where('category', 'Modal Harmony')->where('title', $title)->sole();
            $this->assertTrue($quiz->is_published);
            $this->assertSame(25, $quiz->question_count);
            $this->assertSame('Advanced', $quiz->difficulty);
            $this->assertSame(count(ModalHarmonySamples::choices($number)) * 2 * ($number % 2 === 1 ? 1 : 12), $quiz->questions()->count());
            foreach ($quiz->questions()->with('audioVariants')->get() as $q) {
                $this->assertSame(ModalHarmonySamples::choices($number), $q->choices);
                $this->assertCount(count(ModalHarmonySamples::sounds($number)), $q->audioVariants);
            }
            $this->actingAs(User::factory()->create())->post(route('quiz.start', $quiz))->assertRedirect();
            $attempt = $quiz->attempts()->firstOrFail();
            $this->assertCount(25, $attempt->question_sequence);
            $answer = $quiz->questions()->findOrFail($attempt->question_sequence[0])->answer;
            $this->post(route('quiz.answer', $quiz), ['choice' => $answer])->assertRedirect();
            $this->assertSame(1, $attempt->fresh()->correct_answers);
        }
        $ids = Quiz::where('category', 'Modal Harmony')->orderBy('id')->pluck('id')->all();
        ob_start();
        try {
            require base_path('scripts/audio/build_modal_harmony.php');
        } finally {
            ob_end_clean();
        }
        $this->assertSame($ids, Quiz::where('category', 'Modal Harmony')->orderBy('id')->pluck('id')->all());
        $this->assertSame(0, QuizAttempt::whereIn('quiz_id', $ids)->count());
        $this->assertSame($before, $other->fresh()->load('questions')->toJson());
    }
}
