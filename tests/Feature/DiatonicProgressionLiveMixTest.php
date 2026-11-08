<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;
use App\Models\User;
use App\Support\CadenceSamples;
use App\Support\DiatonicProgressionSamples;
use App\Support\NoteSample;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DiatonicProgressionLiveMixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Cache::flush();
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
        (new \ReflectionProperty(DiatonicProgressionSamples::class, 'voicingCache'))->setValue(null, []);
        foreach (range(24, 95) as $midi) {
            $label = array_keys(NoteSample::CHROMATIC_SLUGS)[$midi % 12];
            Storage::disk('public')->put(NoteSample::path('piano', NoteSample::equivalentDegree($label) ?? $label, intdiv($midi, 12) - 1), 'sample');
        }
    }

    private function midi(array $notes): array
    {
        $naturals = ['Do' => 0, 'Re' => 2, 'Mi' => 4, 'Fa' => 5, 'So' => 7, 'La' => 9, 'Ti' => 11];

        return array_map(fn (array $note): int => ($note['octave'] + 1) * 12 + ($naturals[$note['degree']] ?? array_search($note['degree'], array_keys(NoteSample::CHROMATIC_SLUGS), true)), $notes);
    }

    public function test_b_major_introduction_precedes_four_chords_and_answers_contain_only_those_chords(): void
    {
        $notes = NoteSample::resolve('diatonic-progressions/all-keys-v1/piano/b-4563-v0.wav');
        $this->assertCount(20, $notes);
        $intro = $this->midi(array_slice($notes, 0, 3));
        $this->assertSame([11, 3, 6], array_map(fn (int $pitch): int => $pitch % 12, $intro));
        $this->assertSame([4, 7], [$intro[1] - $intro[0], $intro[2] - $intro[0]]);
        $this->assertSame($intro[0], $this->midi([$notes[3]])[0]);
        $this->assertSame([0.0, 0.0, 0.0, 1.6, ...array_fill(0, 4, 3.2), ...array_fill(0, 4, 4.8), ...array_fill(0, 4, 6.4), ...array_fill(0, 4, 8.0)], array_column($notes, 'offset'));
    }

    public function test_root_open_third_and_fifth_bass_options_are_present(): void
    {
        $closed = $this->midi(array_slice(NoteSample::resolve('diatonic-progressions/all-keys-v1/piano/f-1453-v0.wav'), 4, 4));
        $open = $this->midi(array_slice(NoteSample::resolve('diatonic-progressions/all-keys-v1/piano/f-1453-v1.wav'), 4, 4));
        $first = $this->midi(array_slice(NoteSample::resolve('diatonic-progressions/all-keys-v1/piano/f-1453-v2.wav'), 4, 4));
        $second = $this->midi(array_slice(NoteSample::resolve('diatonic-progressions/all-keys-v1/piano/f-1453-v3.wav'), 4, 4));
        $this->assertSame([53, 57, 60, 65], $closed);
        $this->assertSame([53, 60, 65, 69], $open);
        $this->assertSame(9, $first[0] % 12);
        $this->assertSame(0, $second[0] % 12);
        $next = $this->midi(array_slice(NoteSample::resolve('diatonic-progressions/all-keys-v1/piano/f-1453-v0.wav'), 8, 4));
        $this->assertSame([53, 58, 62, 65], $next);
    }

    public function test_all_keys_patterns_and_variants_use_only_four_voice_diatonic_triads_and_close_movement(): void
    {
        $rows = DiatonicProgressionSamples::manifest();
        $this->assertCount(2400, $rows);
        $this->assertCount(50, array_unique(DiatonicProgressionSamples::PATTERNS));
        foreach (range(2, 6) as $start) {
            $patterns = array_values(array_filter(DiatonicProgressionSamples::PATTERNS, fn (string $pattern): bool => (int) $pattern[0] === $start));
            $expected = match ($start) {
                2 => ['2514', '2435', '2345', '2564', '2415', '2645', '2536', '2451'],
                3 => ['3625', '3425', '3645', '3415', '3251', '3614', '3451', '3641'],
                4 => ['4156', '4736', '4325', '4563', '4516', '4251', '4625', '4165'],
                5 => ['5641', '5147', '5164', '5624', '5362', '5416', '5124', '5642'],
                6 => ['6415', '6251', '6425', '6345', '6245', '6541', '6451', '6145'],
            };
            $this->assertEqualsCanonicalizing($expected, $patterns);
        }
        foreach (array_count_values(array_column($rows, 'key')) as $count) {
            $this->assertSame(200, $count);
        }
        $scale = [1 => 0, 2 => 2, 3 => 4, 4 => 5, 5 => 7, 6 => 9, 7 => 11];
        foreach ($rows as $row) {
            $this->assertNotSame(7, $row['degrees'][0]);
            $this->assertCount(4, $row['degrees']);
            $this->assertSame(implode(' - ', array_map(fn (int $degree): string => $degree.' '.DiatonicProgressionSamples::QUALITIES[$degree], $row['degrees'])), $row['answer']);
            $notes = $row['samples']['piano'];
            $midi = $this->midi($notes);
            [$low, $high] = CadenceSamples::preferredRegister('piano');
            $this->assertGreaterThanOrEqual($low, min($midi));
            $this->assertLessThanOrEqual($high, max($midi));
            $key = array_search($row['key'], CadenceSamples::KEYS, true);
            $this->assertSame([48 + $key, 52 + $key, 55 + $key, 48 + $key], array_slice($midi, 0, 4));
            $previous = null;
            foreach ($row['degrees'] as $index => $degree) {
                $chord = array_slice($midi, 4 + $index * 4, 4);
                $this->assertCount(4, array_unique($chord));
                $classes = array_values(array_unique(array_map(fn (int $pitch): int => $pitch % 12, $chord)));
                $intervals = $degree === 7 ? [0, 3, 6] : (in_array($degree, [2, 3, 6], true) ? [0, 3, 7] : [0, 4, 7]);
                $expected = array_map(fn (int $tone): int => ($key + $scale[$degree] + $tone) % 12, $intervals);
                sort($classes);
                sort($expected);
                $this->assertSame($expected, $classes);
                if ($previous !== null) {
                    foreach ($chord as $voice => $pitch) {
                        $this->assertLessThanOrEqual(4, abs($pitch - $previous[$voice]));
                    }
                }
                $previous = $chord;
            }
        }
        $this->assertNull(NoteSample::resolve('diatonic-progressions/all-keys-v1/piano/f-7362-v0.wav'));
        $this->assertNull(NoteSample::resolve('diatonic-progressions/all-keys-v1/piano/f-1453-v4.wav'));
        $this->assertNull(NoteSample::resolve('diatonic-progressions/all-keys-v1/ep/f-1453-v0.wav'));
        $compact = DiatonicProgressionSamples::mobileManifest([$rows[0]]);
        $this->assertCount(20, $compact[0]['samples']['piano']);
        $this->assertSame($rows[0]['answer'], $compact[0]['answer']);
    }

    public function test_missing_upper_notes_do_not_force_a_large_voice_leading_jump(): void
    {
        Storage::disk('public')->put('notes/vibraphone/gm-bank.json', '{}');
        foreach (range(48, 81) as $midi) {
            if ($midi === 78) {
                continue;
            }
            $label = array_keys(NoteSample::CHROMATIC_SLUGS)[$midi % 12];
            Storage::disk('public')->put(NoteSample::path('vibraphone', NoteSample::equivalentDegree($label) ?? $label, intdiv($midi, 12) - 1), 'sample');
        }
        $notes = NoteSample::resolve('diatonic-progressions/all-keys-v1/vibraphone/d-1625-v1.wav');
        $this->assertCount(20, $notes);
        $chords = array_chunk($this->midi(array_slice($notes, 4)), 4);
        $this->assertGreaterThanOrEqual(14, $chords[0][3] - $chords[0][0]);
        foreach (array_slice($chords, 1) as $index => $chord) {
            foreach ($chord as $voice => $pitch) {
                $this->assertLessThanOrEqual(4, abs($pitch - $chords[$index][$voice]));
            }
        }
    }

    public function test_build_is_repeatable_and_does_not_change_other_progression_quizzes(): void
    {
        $quiz = Quiz::factory()->create(['title' => 'Diatonic Progression #1', 'category' => 'Chord Progressions']);
        $old = QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);
        $attempt = QuizAttempt::factory()->create(['quiz_id' => $quiz->id, 'question_sequence' => [$old->id]]);
        $other = QuizQuestion::factory()->create();
        $before = $other->fresh()->getAttributes();
        ob_start();
        try {
            require base_path('scripts/audio/build_diatonic_progressions.php');
            $ids = $quiz->questions()->orderBy('id')->pluck('id')->all();
            require base_path('scripts/audio/build_diatonic_progressions.php');
        } finally {
            ob_end_clean();
        }
        $this->assertCount(2400, $ids);
        $this->assertSame($ids, $quiz->questions()->orderBy('id')->pluck('id')->all());
        $this->assertDatabaseMissing('quiz_questions', ['id' => $old->id]);
        $this->assertDatabaseMissing('quiz_attempts', ['id' => $attempt->id]);
        $this->assertSame($before, $other->fresh()->getAttributes());
    }

    public function test_admin_preview_member_playback_and_four_chord_grading_agree(): void
    {
        $quiz = Quiz::factory()->create(['title' => 'Diatonic Progression #1', 'slug' => 'diatonic-progression-1', 'category' => 'Chord Progressions', 'question_count' => 1]);
        $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'answer' => '4 Major - 5 Major - 6 Minor - 3 Minor', 'choices' => []]);
        QuizQuestionAudio::create(['quiz_question_id' => $question->id, 'sound' => 'EP', 'audio_path' => 'diatonic-progressions/all-keys-v1/ep/b-4563-v0.wav']);
        $props = $this->actingAs(User::factory()->admin()->create())->get(route('admin.audio-quiz.lesson', ['chord-progressions', 'diatonic-progression-1']))->assertOk()->viewData('page')['props'];
        $preview = collect($props['quiz']['questions'][0]['missing_instrument_previews'])->firstWhere('sound', 'Piano');
        $this->assertCount(20, $preview['notes']);
        $this->actingAs(User::factory()->create())->post(route('quiz.start', $quiz), ['sound' => 'Piano'])->assertRedirect();
        $member = $this->get(route('quiz.play', $quiz))->assertOk()->viewData('page')['props'];
        $this->assertSame($preview['notes'], $member['question']['notes']);
        $this->post(route('quiz.answer', $quiz), ['choice' => $question->answer])->assertRedirect();
        $this->assertTrue(QuizAttempt::where('quiz_id', $quiz->id)->firstOrFail()->answers()->firstOrFail()->correct);
    }
}
