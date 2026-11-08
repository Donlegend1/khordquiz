<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\User;
use App\Support\ModalHarmonySamples;
use App\Support\NoteSample;
use App\Support\QuizCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MinorModalHarmonyTest extends TestCase
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

    public function test_minor_groups_match_the_agreed_labels_and_each_voicing_is_a_compatible_minor_triad(): void
    {
        $specs = [
            3 => [
                ['Phrygian Dominant · ♭II Minor', 1, [0, 1, 4, 5, 7, 8, 10]],
                ['Dorian · II Minor', 2, [0, 2, 3, 5, 7, 9, 10]],
                ['Locrian · ♭III Minor', 3, [0, 1, 3, 5, 6, 8, 10]],
                ['Ionian · III Minor', 4, [0, 2, 4, 5, 7, 9, 11]],
                ['Aeolian · IV Minor', 5, [0, 2, 3, 5, 7, 8, 10]],
            ],
            4 => [
                ['Locrian ♮6 · ♭V Minor', 6, [0, 1, 3, 5, 6, 9, 10]],
                ['Mixolydian · V Minor', 7, [0, 2, 4, 5, 7, 9, 10]],
                ['Harmonic Minor · ♭VI Minor', 8, [0, 2, 3, 5, 7, 8, 11]],
                ['Ionian · VI Minor', 9, [0, 2, 4, 5, 7, 9, 11]],
                ['Phrygian · ♭VII Minor', 10, [0, 1, 3, 5, 7, 8, 10]],
                ['Lydian · VII Minor', 11, [0, 2, 4, 6, 7, 9, 11]],
            ],
        ];
        foreach ($specs as $group => $options) {
            $this->assertSame(array_column($options, 0), ModalHarmonySamples::choices($group * 2 - 1));
            $this->assertSame(array_column($options, 0), ModalHarmonySamples::choices($group * 2));
            foreach (array_values(ModalHarmonySamples::GROUPS[$group]) as $index => [$label, $shapes]) {
                [$answer, $root, $scale] = $options[$index];
                $this->assertSame($answer, $label);
                $this->assertCount(2, $shapes);
                $this->assertNotSame($shapes[0], $shapes[1]);
                foreach ($shapes as $shape) {
                    $this->assertCount(4, $shape);
                    $this->assertSame(48, $shape[0]);
                    $this->assertGreaterThan(48, $shape[1]);
                    $this->assertLessThanOrEqual(59, $shape[1]);
                    $this->assertSame([], array_values(array_diff(array_map(fn (int $p): int => $p % 12, $shape), $scale)));
                    $this->assertEqualsCanonicalizing([0, 3, 7], array_map(fn (int $p): int => ($p - $root) % 12, array_slice($shape, 1)));
                }
            }
        }
        foreach ([5 => 10, 6 => 120, 7 => 12, 8 => 144] as $number => $count) {
            $this->assertCount($count, ModalHarmonySamples::manifest($number));
        }
        $this->assertNull(NoteSample::resolve(ModalHarmonySamples::MINOR_PREFIX.'/1/piano/c-flat-two-minor-v1.wav'));
        $this->assertNull(NoteSample::resolve(ModalHarmonySamples::PREFIX.'/3/piano/c-flat-two-minor-v1.wav'));
        $this->assertNull(NoteSample::resolve(ModalHarmonySamples::MINOR_PREFIX.'/3/piano/c-flat-two-major-v1.wav'));
    }

    public function test_building_only_minor_lessons_preserves_the_first_four_quizzes_and_their_attempts(): void
    {
        $major = [];
        foreach (range(1, 4) as $number) {
            $quiz = Quiz::factory()->create(['category' => 'Modal Harmony', 'title' => 'Modal Harmony #'.$number]);
            $source = ModalHarmonySamples::GROUPS[ModalHarmonySamples::group($number)];
            $id = array_key_first($source);
            $answer = $source[$id][0];
            $question = $quiz->questions()->create(['position' => 1, 'prompt' => 'Keep original', 'answer' => $answer, 'choices' => ModalHarmonySamples::choices($number)]);
            $question->audioVariants()->create(['sound' => 'Piano', 'audio_path' => ModalHarmonySamples::PREFIX.'/'.ModalHarmonySamples::group($number).'/piano/c-'.$id.'-v1.wav']);
            $this->actingAs(User::factory()->create())->post(route('quiz.start', $quiz))->assertRedirect();
            $major[$quiz->id] = $quiz->fresh()->load(['questions.audioVariants', 'attempts'])->toJson();
        }
        $unrelated = Quiz::factory()->create(['category' => 'Scales', 'title' => 'Modal Harmony #5']);
        $unrelatedBefore = $unrelated->fresh()->getAttributes();
        $modalHarmonyLessonNumbers = [5, 6, 7, 8];
        ob_start();
        try {
            require base_path('scripts/audio/build_modal_harmony.php');
        } finally {
            ob_end_clean();
        }
        $this->assertSame(8, Quiz::where('category', 'Modal Harmony')->count());
        foreach ($major as $id => $snapshot) {
            $this->assertSame($snapshot, Quiz::findOrFail($id)->load(['questions.audioVariants', 'attempts'])->toJson());
        }
        $this->assertSame($unrelatedBefore, $unrelated->fresh()->getAttributes());
        $ids = Quiz::where('category', 'Modal Harmony')->orderBy('id')->pluck('id')->all();
        foreach (range(5, 8) as $number) {
            $quiz = Quiz::where('category', 'Modal Harmony')->where('title', 'Modal Harmony #'.$number)->sole();
            $this->assertSame('Advanced', $quiz->difficulty);
            $this->assertTrue($quiz->is_published);
            $this->assertSame(25, $quiz->question_count);
            $this->assertSame(count(ModalHarmonySamples::choices($number)) * 2 * ($number % 2 ? 1 : 12), $quiz->questions()->count());
            foreach ($quiz->questions()->with('audioVariants')->get() as $question) {
                $this->assertSame(ModalHarmonySamples::choices($number), $question->choices);
                $this->assertCount(count(ModalHarmonySamples::sounds($number)), $question->audioVariants);
            }
        }
        ob_start();
        try {
            require base_path('scripts/audio/build_modal_harmony.php');
        } finally {
            ob_end_clean();
        }
        $this->assertSame($ids, Quiz::where('category', 'Modal Harmony')->orderBy('id')->pluck('id')->all());
        foreach ($major as $id => $snapshot) {
            $this->assertSame($snapshot, Quiz::findOrFail($id)->load(['questions.audioVariants', 'attempts'])->toJson());
        }
    }
}
