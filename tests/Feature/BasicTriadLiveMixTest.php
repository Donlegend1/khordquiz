<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;
use App\Models\User;
use App\Support\NoteSample;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BasicTriadLiveMixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Cache::flush();
    }

    private function recordPiano(): void
    {
        foreach (range(36, 95) as $midi) {
            $label = array_keys(NoteSample::CHROMATIC_SLUGS)[$midi % 12];
            Storage::disk('public')->put(NoteSample::path('piano', NoteSample::equivalentDegree($label) ?? $label, intdiv($midi, 12) - 1), 'sample');
        }
    }

    public static function voicings(): array
    {
        return [
            ['basic-triads/piano/sus2.wav', [48, 50, 55]],
            ['basic-triads/piano/sus4.wav', [48, 53, 55]],
            ['basic-triads/level2/piano/db-minor.wav', [49, 52, 56]],
            ['basic-triads/level3/piano/major.wav', [52, 60, 67]],
            ['basic-triads/level4/piano/b-diminished.wav', [62, 71, 77]],
            ['basic-triads/level5/piano/suspended-1st.wav', [50, 55, 60]],
            ['basic-triads/level6/piano/eb-minor-2nd.wav', [58, 63, 66]],
            ['basic-triads/level7/piano/major-1st.wav', [55, 64, 72]],
            ['basic-triads/level8/piano/gb-minor-2nd.wav', [66, 73, 81]],
        ];
    }

    #[DataProvider('voicings')]
    public function test_keys_suspensions_inversions_and_drop_two_are_preserved(string $path, array $expected): void
    {
        $this->recordPiano();
        $notes = NoteSample::resolve($path);
        $this->assertCount(3, $notes);
        $this->assertSame([0.0, 0.0, 0.0], array_column($notes, 'offset'));
        $actual = array_map(function (array $note): int {
            $label = array_search($note['degree'], ['C' => 'Do', 'D' => 'Re', 'E' => 'Mi', 'F' => 'Fa', 'G' => 'So', 'A' => 'La', 'B' => 'Ti'], true);

            return ($note['octave'] + 1) * 12 + array_search($label ?: $note['degree'], array_keys(NoteSample::CHROMATIC_SLUGS), true);
        }, $notes);
        $this->assertSame($expected, $actual);
    }

    public function test_incomplete_banks_and_invalid_paths_do_not_offer_incorrect_chords(): void
    {
        Storage::disk('public')->put('notes/piano/do.wav', 'sample');
        $this->assertNull(NoteSample::resolve('basic-triads/piano/major.wav'));
        $this->assertNull(NoteSample::resolve('basic-triads/level6/piano/c-suspended-root.wav'));
        $this->assertNull(NoteSample::resolve('basic-triads/level2/piano/notakey-major.wav'));
    }

    public function test_unattached_instrument_preview_matches_member_playback_without_changing_the_question(): void
    {
        $this->recordPiano();
        $quiz = Quiz::factory()->create(['title' => 'Basic Triads · Level 1', 'category' => 'Basic Triads', 'question_count' => 1]);
        $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'answer' => 'Suspended', 'choices' => ['Major', 'Suspended']]);
        QuizQuestionAudio::create(['quiz_question_id' => $question->id, 'sound' => 'EP', 'audio_path' => 'basic-triads/ep/sus4.wav']);
        $props = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.audio-quiz.lesson', ['basic-triads', 'basic-triads-level-1']))->assertOk()->viewData('page')['props'];
        $preview = collect($props['quiz']['questions'][0]['missing_instrument_previews'])->firstWhere('sound', 'Piano');
        $this->assertNotNull($preview);
        $this->assertStringContainsString('/fa.wav', $preview['notes'][1]['url']);
        $this->actingAs(User::factory()->create())->post(route('quiz.start', $quiz), ['sound' => 'Piano'])->assertRedirect();
        $notes = $this->get(route('quiz.play', $quiz))->assertOk()->viewData('page')['props']['question']['notes'];
        $this->assertSame($preview['notes'], $notes);
        $this->assertSame('Suspended', $question->fresh()->answer);
        $this->assertCount(1, $question->fresh()->audioVariants);
    }
}
