<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;
use App\Models\User;
use App\Support\CadenceSamples;
use App\Support\NoteSample;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CadenceLiveMixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Cache::flush();
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
        $this->record('piano', 24, 95);
    }

    private function record(string $slug, int $low, int $high): void
    {
        foreach (range($low, $high) as $midi) {
            $label = array_keys(NoteSample::CHROMATIC_SLUGS)[$midi % 12];
            Storage::disk('public')->put(NoteSample::path($slug, NoteSample::equivalentDegree($label) ?? $label, intdiv($midi, 12) - 1), 'sample');
        }
    }

    private function midi(array $notes): array
    {
        $naturals = ['Do' => 0, 'Re' => 2, 'Mi' => 4, 'Fa' => 5, 'So' => 7, 'La' => 9, 'Ti' => 11];

        return array_map(fn (array $note): int => ($note['octave'] + 1) * 12 + ($naturals[$note['degree']] ?? array_search($note['degree'], array_keys(NoteSample::CHROMATIC_SLUGS), true)), $notes);
    }

    public function test_all_chord_options_and_nearby_inversions_resolve_in_f_without_deceptive_cadences(): void
    {
        $recipes = CadenceSamples::recipes();
        $this->assertCount(75, $recipes);
        $this->assertSame(['Authentic' => 12, 'Plagal' => 21, 'Minor Plagal' => 21, 'Sub-Plagal' => 21], array_count_values(array_column($recipes, 'answer')));
        $examples = [
            'bbmaj7-i1-fmaj-i2' => [[62, 65, 69, 70], [60, 65, 69]],
            'c7-i1-fmaj-i0' => [[52, 55, 58, 60], [53, 57, 60]],
            'c7-i2-fmaj-i1' => [[55, 58, 60, 64], [57, 60, 65]],
            'bbmin6-i0-fmaj7-i1' => [[58, 61, 65, 67], [57, 60, 64, 65]],
            'gmin7-i0-fmaj-i1' => [[55, 58, 62, 65], [57, 60, 65]],
        ];
        foreach ($examples as $id => $chords) {
            $this->assertSame($chords, $recipes[$id]['chords']);
        }
        foreach ($recipes as $id => $recipe) {
            $notes = NoteSample::resolve('cadences/all-keys-v3/piano/f-'.$id.'.wav');
            $actual = $this->midi($notes);
            $written = array_merge(...$recipe['chords']);
            $shift = $actual[0] - $written[0];
            $this->assertSame(0, $shift % 12);
            $this->assertSame(array_map(fn (int $pitch): int => $pitch + $shift, $written), $actual);
            $this->assertSame([...array_fill(0, count($recipe['chords'][0]), 0.0), ...array_fill(0, count($recipe['chords'][1]), 1.6)], array_column($notes, 'offset'));
            $this->assertContains($recipe['answer'], CadenceSamples::CHOICES);
            [$first, $last] = $recipe['chords'];
            $this->assertLessThanOrEqual(2, abs($first[0] - $last[0]));
            $this->assertLessThanOrEqual(2, abs(end($first) - end($last)));
            foreach ([[$first, $last], [$last, $first]] as [$from, $to]) {
                foreach ($from as $pitch) {
                    $this->assertLessThanOrEqual(2, min(array_map(fn (int $other): int => abs($pitch - $other), $to)));
                }
            }
        }
        $this->assertNotContains('Deceptive', CadenceSamples::CHOICES);
        $this->assertArrayNotHasKey('c7-i1-fmaj-i2', $recipes);
        $this->assertNull(NoteSample::resolve('cadences/f-v1/piano/c7-i1-fmaj-i2.wav'));
        $this->assertNull(NoteSample::resolve('cadences/all-keys-v3/piano/f-c7-i4-fmaj-i0.wav'));
        $this->assertNull(NoteSample::resolve('cadences/all-keys-v3/piano/f-c7-i0-fmaj-i3.wav'));
        $this->assertNull(NoteSample::resolve('cadences/c-v1/piano/c7-i0-fmaj-i0.wav'));
    }

    public function test_limited_banks_keep_the_same_inversions_and_only_shift_the_whole_cadence_by_an_octave(): void
    {
        $this->record('guitar', 60, 95);
        $path = 'cadences/all-keys-v3/guitar/f-c7-i1-fmaj-i0.wav';
        $this->assertSame([64, 67, 70, 72, 65, 69, 72], $this->midi(NoteSample::resolve($path)));
        $this->assertNull(NoteSample::resolve('cadences/all-keys-v3/ep/f-c7-i1-fmaj-i0.wav'));
        foreach (['bass-guitar', 'upright-bass', 'tuba'] as $slug) {
            $this->assertNull(NoteSample::resolve('cadences/f-v2/'.$slug.'/c7-i1-fmaj-i0.wav'));
        }
    }

    public function test_every_key_uses_a_comfortable_recorded_register_without_changing_the_close_voicings(): void
    {
        $this->record('flute', 24, 95);
        $rows = CadenceSamples::manifest();
        $this->assertCount(900, $rows);
        $counts = array_count_values(array_column($rows, 'key'));
        foreach (CadenceSamples::KEYS as $key) {
            $this->assertSame(75, $counts[$key]);
        }
        foreach ($rows as $row) {
            $template = substr($row['recipe'], strlen($row['key']) + 1);
            $recipe = CadenceSamples::recipes()[$template];
            $written = array_merge(...$recipe['chords']);
            $transpose = array_search($row['key'], CadenceSamples::KEYS, true) - 5;
            foreach (['piano', 'flute'] as $slug) {
                $actual = $this->midi($row['samples'][$slug]);
                [$low, $high] = CadenceSamples::preferredRegister($slug);
                $this->assertGreaterThanOrEqual($low, min($actual));
                $this->assertLessThanOrEqual($high, max($actual));
                $shift = $actual[0] - $written[0] - $transpose;
                $this->assertSame(0, $shift % 12);
                $this->assertSame(array_map(fn (int $pitch): int => $pitch + $transpose + $shift, $written), $actual);
            }
        }
        $piano = NoteSample::resolve('cadences/all-keys-v3/piano/c-c7-i1-fmaj-i0.wav');
        $flute = NoteSample::resolve('cadences/all-keys-v3/flute/c-c7-i1-fmaj-i0.wav');
        $this->assertSame(array_map(fn (int $pitch): int => $pitch + 12, $this->midi($piano)), $this->midi($flute));
        $this->record('ep', 84, 107);
        $this->assertNull(NoteSample::resolve('cadences/all-keys-v3/ep/c-c7-i1-fmaj-i0.wav'));
        $this->assertNull(NoteSample::resolve('cadences/all-keys-v3/piano/notakey-c7-i1-fmaj-i0.wav'));
    }

    public function test_rebuild_removes_deceptive_content_and_is_repeatable_without_touching_other_quizzes(): void
    {
        $quiz = Quiz::factory()->create(['title' => 'Cadences', 'category' => 'Chord Progressions']);
        $old = QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'answer' => 'Deceptive']);
        $oldAttempt = QuizAttempt::factory()->create(['quiz_id' => $quiz->id, 'question_sequence' => [$old->id]]);
        $far = QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'position' => 2, 'answer' => 'Authentic']);
        QuizQuestionAudio::create(['quiz_question_id' => $far->id, 'sound' => 'Piano', 'audio_path' => 'cadences/f-v1/piano/c7-i1-fmaj-i2.wav']);
        $near = QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'position' => 3, 'answer' => 'Authentic']);
        QuizQuestionAudio::create(['quiz_question_id' => $near->id, 'sound' => 'Piano', 'audio_path' => 'cadences/f-v1/piano/c7-i2-fmaj-i1.wav']);
        $other = QuizQuestion::factory()->create();
        $before = $other->fresh()->getAttributes();
        ob_start();
        try {
            require base_path('scripts/audio/build_cadences.php');
            $ids = $quiz->questions()->orderBy('id')->pluck('id')->all();
            $newAttempt = QuizAttempt::factory()->create(['quiz_id' => $quiz->id, 'question_sequence' => array_slice($ids, 0, 25)]);
            require base_path('scripts/audio/build_cadences.php');
        } finally {
            ob_end_clean();
        }
        $this->assertDatabaseMissing('quiz_questions', ['id' => $old->id]);
        $this->assertDatabaseMissing('quiz_questions', ['id' => $far->id]);
        $this->assertDatabaseMissing('quiz_questions', ['id' => $near->id]);
        $this->assertDatabaseMissing('quiz_attempts', ['id' => $oldAttempt->id]);
        $this->assertNotNull($newAttempt->fresh());
        $this->assertCount(900, $ids);
        $this->assertSame($ids, $quiz->questions()->orderBy('id')->pluck('id')->all());
        $this->assertSame($before, $other->fresh()->getAttributes());
        foreach ($quiz->questions()->with('audioVariants')->get() as $question) {
            $this->assertSame(CadenceSamples::CHOICES, $question->choices);
            $this->assertCount(1, $question->audioVariants);
            $this->assertNotNull(NoteSample::resolve($question->audioVariants->first()->audio_path));
        }
    }

    public function test_admin_unattached_live_preview_and_member_playback_use_the_same_cadence(): void
    {
        $quiz = Quiz::factory()->create(['title' => 'Cadences', 'slug' => 'cadences', 'category' => 'Chord Progressions', 'question_count' => 1]);
        $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'answer' => 'Authentic', 'choices' => CadenceSamples::CHOICES]);
        QuizQuestionAudio::create(['quiz_question_id' => $question->id, 'sound' => 'EP', 'audio_path' => 'cadences/all-keys-v3/ep/f-c7-i1-fmaj-i0.wav']);
        $props = $this->actingAs(User::factory()->admin()->create())->get(route('admin.audio-quiz.lesson', ['chord-progressions', 'cadences']))->assertOk()->viewData('page')['props'];
        $preview = collect($props['quiz']['questions'][0]['missing_instrument_previews'])->firstWhere('sound', 'Piano');
        $this->assertNotNull($preview);
        $this->assertSame([0.0, 0.0, 0.0, 0.0, 1.6, 1.6, 1.6], array_column($preview['notes'], 'offset'));
        $this->actingAs(User::factory()->create())->post(route('quiz.start', $quiz), ['sound' => 'Piano'])->assertRedirect();
        $notes = $this->get(route('quiz.play', $quiz))->assertOk()->viewData('page')['props']['question']['notes'];
        $this->assertSame($preview['notes'], $notes);
        $this->post(route('quiz.answer', $quiz), ['choice' => 'Authentic'])->assertRedirect();
        $this->assertTrue(QuizAttempt::where('quiz_id', $quiz->id)->firstOrFail()->answers()->firstOrFail()->correct);
    }
}
