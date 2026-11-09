<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;
use App\Models\User;
use App\Support\DiatonicProgressionSamples;
use App\Support\NoteSample;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminQuizPaginationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Cache::flush();
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
        (new \ReflectionProperty(DiatonicProgressionSamples::class, 'voicingCache'))->setValue(null, []);
        foreach (range(48, 76) as $midi) {
            $label = array_keys(NoteSample::CHROMATIC_SLUGS)[$midi % 12];
            Storage::disk('public')->put(NoteSample::path('piano', NoteSample::equivalentDegree($label) ?? $label, intdiv($midi, 12) - 1), 'sample');
        }
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_large_lesson_pages_keep_playback_data_bounded_and_every_question_accessible(): void
    {
        $quiz = Quiz::factory()->create(['category' => 'Chord Progressions', 'title' => 'Diatonic Progression #1']);
        $ids = [];
        foreach (range(1, 78) as $position) {
            $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'position' => $position, 'answer' => '1 Major - 4 Major - 5 Major - 3 Minor', 'choices' => []]);
            QuizQuestionAudio::create(['quiz_question_id' => $question->id, 'sound' => 'Piano', 'audio_path' => 'diatonic-progressions/all-keys-v1/piano/f-1453-v0.wav']);
            $ids[] = $question->id;
        }
        QuizQuestionAudio::create(['quiz_question_id' => end($ids), 'sound' => 'EP', 'audio_path' => 'diatonic-progressions/all-keys-v1/ep/f-1453-v0.wav']);
        $url = route('admin.audio-quiz.lesson', ['chord-progressions', 'diatonic-progression-1']);
        $seen = [];
        foreach (range(1, 4) as $page) {
            $props = $this->get($url.'?page='.$page)->assertOk()->viewData('page')['props']['quiz'];
            $this->assertSame(78, $props['questions_total']);
            $this->assertSame($page, $props['pagination']['current_page']);
            $this->assertSame(4, $props['pagination']['last_page']);
            $this->assertCount($page === 4 ? 3 : 25, $props['questions']);
            $this->assertContains('EP', $props['audio_sounds']);
            $this->assertSame($page === 4, $props['pagination']['next_url'] === null);
            $this->assertSame($page === 1, $props['pagination']['previous_url'] === null);
            foreach ($props['questions'] as $question) {
                $seen[] = $question['id'];
                $this->assertCount(20, collect($question['audio_variants'])->firstWhere('sound', 'Piano')['notes']);
            }
        }
        $this->assertSame($ids, $seen);
        $last = $this->get($url.'?page=999')->assertOk()->viewData('page')['props']['quiz'];
        $this->assertSame(4, $last['pagination']['current_page']);
        $this->assertCount(3, $last['questions']);
        $first = $this->get($url.'?page=-1')->assertOk()->viewData('page')['props']['quiz'];
        $this->assertSame(1, $first['pagination']['current_page']);
        $this->assertSame(78, $quiz->questions()->count());
    }

    public function test_empty_lesson_still_opens_with_accurate_totals(): void
    {
        Quiz::factory()->create(['category' => 'Chord Progressions', 'title' => 'Diatonic Progression #1']);
        $props = $this->get(route('admin.audio-quiz.lesson', ['chord-progressions', 'diatonic-progression-1']))->assertOk()->viewData('page')['props']['quiz'];
        $this->assertSame(0, $props['questions_total']);
        $this->assertCount(0, $props['questions']);
        $this->assertNull($props['pagination']['from']);
        $this->assertNull($props['pagination']['next_url']);
    }
}
