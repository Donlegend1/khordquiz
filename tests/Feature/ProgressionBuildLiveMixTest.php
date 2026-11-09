<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Support\DiatonicProgressionSamples;
use App\Support\NoteSample;
use App\Support\ProgressionBuildSamples;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProgressionBuildLiveMixTest extends TestCase
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
    }

    public function test_seventy_unique_patterns_follow_the_approved_passing_rules(): void
    {
        $patterns = ProgressionBuildSamples::PATTERNS;
        $this->assertCount(70, $patterns);
        $this->assertCount(70, array_unique(array_map('json_encode', $patterns)));
        $rules = ['2 Major' => ['5 Major', '4 Major'], '3 Major' => ['4 Major', '6 Minor'], '4 Minor' => ['1 Major'], '5 Minor' => ['4 Major'], '6 Major' => ['2 Minor', '4 Major'], '♭7 Major' => ['4 Major', '5 Major', '1 Major'], '1 Diminished' => ['1 Major'], '2 Diminished' => ['1 Major', '6 Minor'], '3 Diminished' => ['4 Major', '4 Minor', '2 Diminished'], '4 Diminished' => ['1 Major', '6 Minor'], '5 Diminished' => ['4 Major', '2 Minor'], '6 Diminished' => ['5 Major', '1 Major'], '7 Diminished' => ['1 Major']];
        foreach ($patterns as $pattern) {
            $this->assertCount(4, $pattern);
            $this->assertTrue(array_any($pattern, fn (int $chord): bool => $chord >= 8 && $chord <= 19));
            foreach ($pattern as $index => $chord) {
                $label = str_replace('Sus2', 'Major', ProgressionBuildSamples::LABELS[$chord]);
                if (str_contains(ProgressionBuildSamples::LABELS[$chord], 'Sus4')) {
                    $this->assertLessThan(3, $index);
                    $this->assertSame(str_replace('Sus4', 'Major', $label), ProgressionBuildSamples::LABELS[$pattern[$index + 1]]);
                }
                if (isset($rules[$label])) {
                    $this->assertLessThan(3, $index);
                    $this->assertContains(str_replace('Sus2', 'Major', ProgressionBuildSamples::LABELS[$pattern[$index + 1]]), $rules[$label]);
                }
            }
        }
    }

    public function test_suspended_groups_and_equivalent_names_are_graded_fairly(): void
    {
        $added = array_slice(ProgressionBuildSamples::PATTERNS, 50);
        $this->assertCount(0, array_filter($added, fn (array $pattern): bool => array_any($pattern, fn (int $chord): bool => $chord >= 20 && $chord <= 25)));
        $this->assertCount(20, array_filter($added, fn (array $pattern): bool => array_any($pattern, fn (int $chord): bool => $chord >= 26 && $chord <= 31)));
        foreach (['1 Sus2' => '5 Sus4', '2 Sus2' => '6 Sus4', '3 Sus2' => '7 Sus4', '4 Sus2' => '1 Sus4', '5 Sus2' => '2 Sus4', '6 Sus2' => '3 Sus4', '4 Sus4' => '♭7 Sus2'] as $original => $alias) {
            $this->assertTrue(ProgressionBuildSamples::answersMatch($alias.' - 1 Major - 4 Major - 5 Major', $original.' - 1 Major - 4 Major - 5 Major'));
            $this->assertFalse(ProgressionBuildSamples::answersMatch($alias.' - 1 Minor - 4 Major - 5 Major', $original.' - 1 Major - 4 Major - 5 Major'));
        }
        $this->assertFalse(ProgressionBuildSamples::answersMatch('1 Sus4 - 1 Major - 4 Major - 5 Major', '1 Sus2 - 1 Major - 4 Major - 5 Major'));
        $this->assertFalse(ProgressionBuildSamples::answersMatch('1 Major', '1 Major'));
    }

    public function test_every_pattern_has_four_voice_triads_tonic_introduction_and_close_movement(): void
    {
        $rows = ProgressionBuildSamples::manifest();
        $this->assertCount(840, $rows);
        $this->assertSame(['f' => 70, 'c' => 70, 'db' => 70, 'd' => 70, 'eb' => 70, 'e' => 70, 'gb' => 70, 'g' => 70, 'ab' => 70, 'a' => 70, 'bb' => 70, 'b' => 70], array_count_values(array_column($rows, 'key')));
        $this->assertCount(70, array_unique(array_column($rows, 'answer')));
        $this->assertCount(840, array_unique(array_map(fn (array $row): string => preg_replace('/-v[0-3]$/', '', $row['recipe']), $rows)));
        $this->assertCount(4, array_unique(array_map(fn (array $row): string => substr($row['recipe'], -1), $rows)));
        $names = ['Do', 'C#/Db', 'Re', 'D#/Eb', 'Mi', 'Fa', 'F#/Gb', 'So', 'G#/Ab', 'La', 'A#/Bb', 'Ti'];
        $roots = ['1' => 0, '2' => 2, '3' => 4, '4' => 5, '5' => 7, '6' => 9, '7' => 11, '♭7' => 10];
        foreach ($rows as $row) {
            $key = ['f' => 5, 'c' => 0, 'db' => 1, 'd' => 2, 'eb' => 3, 'e' => 4, 'gb' => 6, 'g' => 7, 'ab' => 8, 'a' => 9, 'bb' => 10, 'b' => 11][$row['key']];
            $notes = $row['samples']['piano'];
            $this->assertCount(20, $notes);
            $midi = array_map(fn (array $note): int => ($note['octave'] + 1) * 12 + array_search($note['degree'], $names, true), $notes);
            $this->assertSame([48 + $key, 52 + $key, 55 + $key, 48 + $key], array_slice($midi, 0, 4));
            $this->assertSame([0.0, 0.0, 0.0, 1.6, ...array_fill(0, 4, 3.2), ...array_fill(0, 4, 4.8), ...array_fill(0, 4, 6.4), ...array_fill(0, 4, 8.0)], array_column($notes, 'offset'));
            $previous = null;
            foreach (explode(' - ', $row['answer']) as $index => $label) {
                [$degree,$quality] = explode(' ', $label);
                $chord = array_slice($midi, 4 + $index * 4, 4);
                $this->assertCount(4, array_unique($chord));
                $intervals = match ($quality) {
                    'Major' => [0, 4, 7], 'Minor' => [0, 3, 7], 'Diminished' => [0, 3, 6], 'Sus2' => [0, 2, 7], 'Sus4' => [0, 5, 7]
                };
                $this->assertEqualsCanonicalizing(array_map(fn (int $tone): int => ($key + $roots[$degree] + $tone) % 12, $intervals), array_values(array_unique(array_map(fn (int $pitch): int => $pitch % 12, $chord))));
                if ($previous !== null) {
                    foreach ($chord as $voice => $pitch) {
                        $this->assertLessThanOrEqual(4, abs($pitch - $previous[$voice]));
                    }
                }
                $previous = $chord;
            }
        }
        $this->assertNull(NoteSample::resolve('progression-build-one/f-major-v1/piano/f-p999-v0.wav'));
        $this->assertNull(NoteSample::resolve('progression-build-one/f-major-v1/piano/invalid-p001-v0.wav'));
    }

    public function test_equivalent_suspended_answer_is_accepted_by_quiz_grading(): void
    {
        $quiz = Quiz::factory()->create(['title' => 'Progression Build #1', 'category' => 'Chord Progressions', 'question_count' => 1]);
        QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'choices' => [], 'answer' => '♭7 Major - 4 Major - 1 Sus4 - 1 Major', 'audio_path' => 'progression-build-one/f-major-v1/piano/f-p069-v0.wav']);
        $this->actingAs(User::factory()->create())->post(route('quiz.start', $quiz), ['sound' => 'Piano'])->assertRedirect();
        $this->post(route('quiz.answer', $quiz), ['choice' => '♭7 Major - 4 Major - 4 Sus2 - 1 Major'])->assertRedirect();
        $this->assertTrue($quiz->attempts()->firstOrFail()->answers()->firstOrFail()->correct);
    }

    public function test_build_preserves_other_quizzes_and_supports_playback_and_grading(): void
    {
        $other = QuizQuestion::factory()->create();
        $before = $other->fresh()->getAttributes();
        ob_start();
        try {
            require base_path('scripts/audio/build_progression_build_one.php');
            $quiz = Quiz::where('title', 'Progression Build #1')->firstOrFail();
            $ids = $quiz->questions()->pluck('id')->all();
            require base_path('scripts/audio/build_progression_build_one.php');
        } finally {
            ob_end_clean();
        }
        $this->assertCount(840, $ids);
        $this->assertSame(range(1, 840), $quiz->questions()->pluck('position')->all());
        $this->assertSame($ids, $quiz->questions()->pluck('id')->all());
        $this->assertSame($before, $other->fresh()->getAttributes());
        $admin = $this->actingAs(User::factory()->admin()->create())->get(route('admin.audio-quiz.lesson', ['chord-progressions', 'progression-build-1']))->assertOk()->viewData('page')['props'];
        $this->assertCount(20, $admin['quiz']['questions'][0]['audio_variants'][0]['notes']);
        $this->actingAs(User::factory()->create())->post(route('quiz.start', $quiz), ['sound' => 'Piano'])->assertRedirect();
        $member = $this->get(route('quiz.play', $quiz))->assertOk()->viewData('page')['props'];
        $this->assertCount(20, $member['question']['notes']);
        $question = $quiz->questions()->whereKey($quiz->attempts()->firstOrFail()->questionIdAt(1))->firstOrFail();
        $this->post(route('quiz.answer', $quiz), ['choice' => $question->answer])->assertRedirect();
        $this->assertTrue($quiz->attempts()->firstOrFail()->answers()->firstOrFail()->correct);
    }
}
