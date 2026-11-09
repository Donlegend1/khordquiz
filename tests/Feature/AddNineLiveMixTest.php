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

class AddNineLiveMixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Cache::flush();
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
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
            ['add9/piano/major.wav', [53, 55, 57, 60]],
            ['add9/level2/piano/db-minor.wav', [49, 51, 52, 56]],
            ['add9/level3/piano/minor-3rd.wav', [60, 65, 67, 68]],
            ['add9/level4/piano/eb-augmented-2nd.wav', [55, 59, 63, 65]],
            ['add9/level5/piano/major.wav', [57, 65, 67, 72]],
            ['add9/level6/piano/b-diminished.wav', [62, 71, 73, 77]],
            ['add9/level7/piano/minor-1st.wav', [60, 67, 68, 77]],
            ['add9/level8/piano/gb-augmented-3rd.wav', [68, 74, 78, 82]],
        ];
    }

    #[DataProvider('voicings')]
    public function test_added_seconds_keys_inversions_and_drop_two_are_preserved(string $path, array $expected): void
    {
        $this->recordPiano();
        $notes = NoteSample::resolve($path);
        $this->assertCount(4, $notes);
        $this->assertSame([0.0, 0.0, 0.0, 0.0], array_column($notes, 'offset'));
        $actual = array_map(function (array $note): int {
            $label = array_search($note['degree'], ['C' => 'Do', 'D' => 'Re', 'E' => 'Mi', 'F' => 'Fa', 'G' => 'So', 'A' => 'La', 'B' => 'Ti'], true);

            return ($note['octave'] + 1) * 12 + array_search($label ?: $note['degree'], array_keys(NoteSample::CHROMATIC_SLUGS), true);
        }, $notes);
        $this->assertSame($expected, $actual);
    }

    public function test_incomplete_banks_and_invalid_paths_do_not_offer_incorrect_chords(): void
    {
        Storage::disk('public')->put('notes/piano/do.wav', 'sample');
        $this->assertNull(NoteSample::resolve('add9/piano/major.wav'));
        $this->assertNull(NoteSample::resolve('add9/level6/piano/c-major-root.wav'));
        $this->assertNull(NoteSample::resolve('add9/level2/piano/notakey-major.wav'));
    }

    public function test_range_fitting_moves_the_complete_chord_by_an_octave(): void
    {
        foreach (['Fa', 'So', 'La', 'Do'] as $degree) {
            Storage::disk('public')->put(NoteSample::path('piano', $degree, $degree === 'Do' ? 5 : 4), 'sample');
        }
        $notes = NoteSample::resolve('add9/piano/major.wav');
        $this->assertCount(4, $notes);
        $this->assertSame([4, 4, 4, 5], array_column($notes, 'octave'));
    }

    public function test_range_fitting_never_moves_individual_chord_tones(): void
    {
        foreach (['Fa', 'La', 'Do'] as $degree) {
            Storage::disk('public')->put(NoteSample::path('piano', $degree, 3), 'sample');
        }
        Storage::disk('public')->put(NoteSample::path('piano', 'So', 4), 'sample');
        $this->assertNull(NoteSample::resolve('add9/piano/major.wav'));
    }

    public function test_unattached_instrument_preview_matches_member_playback_without_changing_the_question(): void
    {
        $this->recordPiano();
        $quiz = Quiz::factory()->create(['title' => 'Add 9 · Level 3', 'category' => 'Add 9', 'question_count' => 1]);
        $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'answer' => 'Major', 'choices' => ['Major', 'Minor']]);
        QuizQuestionAudio::create(['quiz_question_id' => $question->id, 'sound' => 'EP', 'audio_path' => 'add9/level3/ep/major-3rd.wav']);
        $props = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.audio-quiz.lesson', ['add-9', 'add-9-level-3']))->assertOk()->viewData('page')['props'];
        $preview = collect($props['quiz']['questions'][0]['missing_instrument_previews'])->firstWhere('sound', 'Piano');
        $this->assertNotNull($preview);
        $this->assertStringContainsString('/do-c4.wav', $preview['notes'][0]['url']);
        $this->actingAs(User::factory()->create())->post(route('quiz.start', $quiz), ['sound' => 'Piano'])->assertRedirect();
        $notes = $this->get(route('quiz.play', $quiz))->assertOk()->viewData('page')['props']['question']['notes'];
        $this->assertSame($preview['notes'], $notes);
        $this->assertSame('Major', $question->fresh()->answer);
        $this->assertCount(1, $question->fresh()->audioVariants);
    }
}
