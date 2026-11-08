<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;
use App\Models\User;
use App\Support\IntervalSamples;
use App\Support\NoteSample;
use App\Support\QuizCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IntervalLiveMixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Cache::flush();
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
    }

    private function recordBank(string $slug, int $low, int $high, bool $legacy, int $registerOffset = 0): void
    {
        foreach (range($low, $high) as $midi) {
            $label = array_keys(NoteSample::CHROMATIC_SLUGS)[$midi % 12];
            $octave = intdiv($midi, 12) - 1 + $registerOffset - ($legacy && str_contains($label, '#') ? 1 : 0);
            Storage::disk('public')->put(NoteSample::path($slug, NoteSample::equivalentDegree($label) ?? $label, $octave), 'sample');
        }
    }

    public static function intervalAnswers(): array
    {
        return [
            ['Minor 2nd', 1], ['Major 2nd', 2], ['Minor 3rd', 3], ['Major 3rd', 4],
            ['Perfect 4th', 5], ['Tri Tone', 6], ['Perfect 5th', 7], ['Minor 6th', 8],
            ['Major 6th', 9], ['Minor 7th', 10], ['Major 7th', 11], ['Octave', 12],
        ];
    }

    #[DataProvider('intervalAnswers')]
    public function test_every_interval_uses_the_exact_distance_and_timing(string $answer, int $semitones): void
    {
        $this->recordBank('piano', 36, 95, false);
        $this->recordBank('tuba', 36, 60, true);
        $this->recordBank('voice', 43, 79, true);
        $this->recordBank('organ', 36, 84, false, 1);
        $this->recordBank('harpejji', 36, 84, false, 1);
        $this->recordBank('upright-bass', 24, 59, false, 2);
        $this->recordBank('celesta', 48, 72, false);
        $this->recordBank('vibraphone', 48, 72, false);
        foreach (['piano', 'tuba', 'voice', 'organ', 'harpejji', 'upright-bass', 'celesta', 'vibraphone'] as $slug) {
            $pair = IntervalSamples::pairFor($answer, $slug);
            $this->assertNotNull($pair);
            $this->assertSame($semitones, $pair[1] - $pair[0]);
            foreach (['melodic', 'harmonic'] as $timing) {
                $path = "reference-intervals/live-v1/{$timing}/{$slug}/{$pair[0]}-{$pair[1]}.wav";
                $notes = NoteSample::resolve($path);
                $this->assertCount(2, $notes);
                $this->assertSame(0.0, $notes[0]['offset']);
                $this->assertSame($timing === 'melodic' ? 0.75 : 0.0, $notes[1]['offset']);
                foreach ($notes as $index => $note) {
                    $this->assertTrue(NoteSample::sampleExists(NoteSample::path($slug, $note['degree'], $note['octave'])));
                    $letters = ['Do' => 0, 'Re' => 2, 'Mi' => 4, 'Fa' => 5, 'So' => 7, 'La' => 9, 'Ti' => 11];
                    $pitchClass = $letters[$note['degree']] ?? array_search($note['degree'], array_keys(NoteSample::CHROMATIC_SLUGS), true);
                    $registerOffset = ['organ' => 1, 'harpejji' => 1, 'upright-bass' => 2][$slug] ?? 0;
                    $trueOctave = $note['octave'] - $registerOffset + (in_array($slug, ['tuba', 'voice'], true) && str_contains($note['degree'], '#') ? 1 : 0);
                    $this->assertSame($pair[$index], ($trueOctave + 1) * 12 + $pitchClass);
                }
            }
        }
    }

    public function test_missing_and_invalid_pairs_fail_without_substituting_pitches(): void
    {
        $this->recordBank('piano', 60, 60, false);
        $this->assertNull(IntervalSamples::pairFor('Minor 2nd', 'piano'));
        $this->assertNull(IntervalSamples::pairFor('Not an interval', 'piano'));
        $this->assertNull(NoteSample::resolve('reference-intervals/live-v1/melodic/piano/60-73.wav'));
        $this->assertNull(NoteSample::resolve('reference-intervals/live-v1/harmonic/piano/60-60.wav'));
        $this->assertNull(NoteSample::fromRecordedMidiPitch(72, 'piano'));
    }

    public function test_catalog_offers_all_twenty_eight_instruments_for_all_six_quizzes(): void
    {
        foreach (QuizCatalog::subcategories('Intervals') as $title) {
            $this->assertSame(QuizCatalog::allInstruments(), QuizCatalog::soundsFor('Intervals', $title));
            $this->assertCount(28, QuizCatalog::soundsFor('Intervals', $title));
        }
    }

    public function test_admin_preview_and_member_prompt_use_the_same_two_samples(): void
    {
        $this->recordBank('piano', 60, 72, false);
        $quiz = Quiz::factory()->create(['title' => 'Melodic Intervals: General', 'category' => 'Intervals', 'question_count' => 1]);
        $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'answer' => 'Tri Tone', 'choices' => ['Tri Tone']]);
        QuizQuestionAudio::create(['quiz_question_id' => $question->id, 'sound' => 'Piano', 'audio_path' => 'reference-intervals/live-v1/melodic/piano/60-66.wav']);
        $admin = User::factory()->admin()->create();
        $adminProps = $this->actingAs($admin)->get(route('admin.audio-quiz.lesson', ['intervals', 'melodic-intervals-general']))->assertOk()->viewData('page')['props'];
        $adminNotes = $adminProps['quiz']['questions'][0]['audio_variants'][0]['notes'];
        $this->assertCount(2, $adminNotes);
        $member = User::factory()->create();
        $this->actingAs($member)->post(route('quiz.start', $quiz), ['sound' => 'Piano']);
        $memberProps = $this->get(route('quiz.play', $quiz))->assertOk()->viewData('page')['props'];
        $this->assertSame($adminNotes, $memberProps['question']['notes']);
    }

    public function test_an_unattached_instrument_gets_a_real_live_preview_and_member_audio(): void
    {
        $this->recordBank('tuba', 36, 60, true);
        $quiz = Quiz::factory()->create(['title' => 'Harmonic Non-diatonic Intervals', 'category' => 'Intervals', 'question_count' => 1]);
        QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'answer' => 'Minor 3rd', 'choices' => ['Minor 3rd']]);
        $admin = User::factory()->admin()->create();
        $props = $this->actingAs($admin)->get(route('admin.audio-quiz.lesson', ['intervals', 'harmonic-non-diatonic-intervals']))->assertOk()->viewData('page')['props'];
        $preview = collect($props['quiz']['questions'][0]['missing_instrument_previews'])->firstWhere('sound', 'Tuba');
        $this->assertNotNull($preview);
        $this->assertSame([0.0, 0.0], array_column($preview['notes'], 'offset'));
        $this->actingAs(User::factory()->create())->post(route('quiz.start', $quiz), ['sound' => 'Tuba']);
        $notes = $this->get(route('quiz.play', $quiz))->assertOk()->viewData('page')['props']['question']['notes'];
        $this->assertSame($preview['notes'], $notes);
    }
}
