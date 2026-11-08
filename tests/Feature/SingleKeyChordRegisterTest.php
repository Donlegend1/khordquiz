<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;
use App\Support\NoteSample;
use App\Support\SingleKeyChordCoverage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SingleKeyChordRegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Cache::flush();
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
        foreach (range(24, 107) as $midi) {
            $label = array_keys(NoteSample::CHROMATIC_SLUGS)[$midi % 12];
            Storage::disk('public')->put(NoteSample::path('piano', NoteSample::equivalentDegree($label) ?? $label, intdiv($midi, 12) - 1), 'sample');
        }
    }

    public static function chords(): array
    {
        return [
            ['basic-triads', 'major', [53, 57, 60]],
            ['add9', 'major', [53, 55, 57, 60]],
            ['seventh-degree-chords', 'major7', [53, 57, 60, 64]],
            ['secondary-seventh-chords', 'aug7', [53, 57, 61, 63]],
            ['ninth-degree-chords', 'major9', [53, 57, 60, 64, 67]],
            ['secondary-ninth-chords', 'maj9s5', [53, 57, 61, 64, 67]],
            ['eleventh-degree-chords', 'maj9s11', [53, 57, 60, 64, 67, 71]],
            ['secondary-eleventh-chords', 'maj9s5s11', [53, 57, 61, 64, 67, 71]],
            ['thirteenth-degree-chords', 'maj13s11', [53, 57, 60, 64, 67, 71, 74]],
        ];
    }

    #[DataProvider('chords')]
    public function test_implicit_single_key_is_f_and_explicit_keys_are_unchanged(string $prefix, string $quality, array $expected): void
    {
        $single = in_array($prefix, ['basic-triads', 'add9'], true)
            ? $prefix.'/piano/'.$quality.'.wav'
            : $prefix.'/level1/piano/'.$quality.'.wav';
        $notes = NoteSample::resolve($single);
        $this->assertNotNull($notes);
        $this->assertSame(NoteSample::resolve($prefix.'/level2/piano/f-'.$quality.'.wav'), $notes);
        $toMidi = function (array $notes): array {
            $natural = ['Do' => 0, 'Re' => 2, 'Mi' => 4, 'Fa' => 5, 'So' => 7, 'La' => 9, 'Ti' => 11];

            return array_map(fn ($note) => ($note['octave'] + 1) * 12 + ($natural[$note['degree']] ?? array_search($note['degree'], array_keys(NoteSample::CHROMATIC_SLUGS), true)), $notes);
        };
        $this->assertSame($expected, $toMidi($notes));
        $this->assertSame(array_map(fn ($n) => $n - 5, $expected), $toMidi(NoteSample::resolve($prefix.'/level2/piano/c-'.$quality.'.wav')));
    }

    public function test_coverage_updates_only_audio_rows_on_single_key_lessons(): void
    {
        $quiz = Quiz::factory()->create(['category' => 'Basic Triads', 'title' => 'Basic Triads · Level 1']);
        $single = QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);
        $second = QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'position' => 2]);
        $piano = QuizQuestionAudio::create(['quiz_question_id' => $single->id, 'sound' => 'Piano', 'audio_path' => 'basic-triads/piano/major.wav']);
        $bad = QuizQuestionAudio::create(['quiz_question_id' => $single->id, 'sound' => 'EP', 'audio_path' => 'basic-triads/ep/major.wav']);
        QuizQuestionAudio::create(['quiz_question_id' => $second->id, 'sound' => 'EP', 'audio_path' => 'basic-triads/ep/minor.wav']);
        $allKeys = Quiz::factory()->create(['category' => 'Basic Triads', 'title' => 'Basic Triads · Level 2']);
        $other = QuizQuestion::factory()->create(['quiz_id' => $allKeys->id]);
        $untouched = QuizQuestionAudio::create(['quiz_question_id' => $other->id, 'sound' => 'EP', 'audio_path' => 'basic-triads/level2/ep/c-major.wav']);
        $attempt = QuizAttempt::factory()->create(['quiz_id' => $quiz->id, 'question_sequence' => [$single->id, $second->id]]);
        $beforeQuestion = $single->fresh()->getAttributes();
        $beforeAttempt = $attempt->fresh()->getAttributes();
        $plan = SingleKeyChordCoverage::plan();
        $this->assertSame(1, $plan['lessons']);
        $this->assertSame(2, $plan['questions']);
        SingleKeyChordCoverage::apply($plan);
        $this->assertDatabaseMissing('quiz_question_audio', ['id' => $bad->id]);
        $this->assertSame('basic-triads/piano/major.wav', $piano->fresh()->audio_path);
        $this->assertNotNull($untouched->fresh());
        $this->assertSame($beforeQuestion, $single->fresh()->getAttributes());
        $this->assertSame($beforeAttempt, $attempt->fresh()->getAttributes());
        $this->assertDatabaseHas('quiz_question_audio', ['quiz_question_id' => $second->id, 'sound' => 'Piano']);
        $again = SingleKeyChordCoverage::plan();
        $this->assertSame([], $again['remove']);
        $this->assertSame([], $again['add']);
    }

    public function test_unplayable_questions_abort_planning_before_any_database_changes(): void
    {
        $quiz = Quiz::factory()->create(['category' => 'Add 9', 'title' => 'Add 9 · Level 1']);
        QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);
        $this->expectException(\RuntimeException::class);
        SingleKeyChordCoverage::plan();
    }
}
