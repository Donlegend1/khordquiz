<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Support\DiatonicProgressionSamples;
use App\Support\NoteSample;
use App\Support\SeventhProgressionBuildSamples;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeventhProgressionBuildLiveMixTest extends TestCase
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

    public function test_ninety_unique_four_chord_patterns_follow_every_approved_destination(): void
    {
        $patterns = SeventhProgressionBuildSamples::PATTERNS;
        $this->assertCount(90, $patterns);
        $this->assertCount(90, array_unique(array_map('json_encode', $patterns)));
        $rules = ['5 Dominant 7th' => ['1 Major 7th'], '2 Dominant 7th' => ['5 Dominant 7th', '4 Major 7th'], '2 Minor 7(♭5)' => ['1 Major 7th'], '3 Dominant 7th' => ['4 Major 7th', '6 Minor 7th'], '3 Minor 7(♭5)' => ['4 Major 7th', '2 Minor 7(♭5)', '6 Dominant 7th'], '4 Dominant 7th' => ['1 Dominant 7th', '3 Minor 7(♭5)'], '5 Minor 7th' => ['1 Dominant 7th'], '6 Dominant 7th' => ['2 Minor 7th', '2 Dominant 7th'], '6 Minor 7(♭5)' => ['2 Minor 7(♭5)'], '7 Dominant 7th' => ['3 Minor 7th'], '1 Dominant 7th' => ['4 Major 7th']];
        $edges = [];
        $suspended = 0;
        foreach ($patterns as $pattern) {
            $this->assertCount(4, $pattern);
            $this->assertTrue(array_any($pattern, fn (int $chord): bool => $chord >= 8 && $chord <= 17));
            $labels = array_map(fn (int $chord): string => SeventhProgressionBuildSamples::LABELS[$chord], $pattern);
            $hasSuspension = false;
            foreach ($labels as $index => $label) {
                if (str_ends_with($label, '7 Sus4')) {
                    $hasSuspension = true;
                    $this->assertLessThan(3, $index);
                    $this->assertSame(str_replace('7 Sus4', 'Dominant 7th', $label), $labels[$index + 1]);
                }
                if (isset($rules[$label])) {
                    $this->assertLessThan(3, $index);
                    $this->assertContains($labels[$index + 1], $rules[$label]);
                    $edges[$label][] = $labels[$index + 1];
                }
            }
            $suspended += $hasSuspension ? 1 : 0;
        }
        $this->assertSame(20, $suspended);
        foreach ($rules as $source => $destinations) {
            foreach ($destinations as $destination) {
                $this->assertContains($destination, $edges[$source] ?? []);
            }
        }
    }

    public function test_enabled_keys_have_fixed_references_and_correct_five_note_voicings(): void
    {
        $rows = SeventhProgressionBuildSamples::manifest();
        $this->assertCount(1080, $rows);
        $this->assertSame(['c' => 90, 'db' => 90, 'd' => 90, 'eb' => 90, 'e' => 90, 'f' => 90, 'gb' => 90, 'g' => 90, 'ab' => 90, 'a' => 90, 'bb' => 90, 'b' => 90], array_count_values(array_column($rows, 'key')));
        $this->assertCount(90, array_unique(array_column($rows, 'answer')));
        $this->assertCount(1080, array_unique(array_column($rows, 'recipe')));
        $names = ['Do', 'C#/Db', 'Re', 'D#/Eb', 'Mi', 'Fa', 'F#/Gb', 'So', 'G#/Ab', 'La', 'A#/Bb', 'Ti'];
        $scale = ['1' => 0, '2' => 2, '3' => 4, '4' => 5, '5' => 7, '6' => 9, '7' => 11];
        $intervals = ['Major 7th' => [0, 4, 7, 11], 'Minor 7th' => [0, 3, 7, 10], 'Dominant 7th' => [0, 4, 7, 10], 'Minor 7(♭5)' => [0, 3, 6, 10], '7 Sus4' => [0, 5, 7, 10]];
        foreach ($rows as $row) {
            $key = ['c' => 0, 'db' => 1, 'd' => 2, 'eb' => 3, 'e' => 4, 'f' => 5, 'gb' => 6, 'g' => 7, 'ab' => 8, 'a' => 9, 'bb' => 10, 'b' => 11][$row['key']];
            $notes = $row['samples']['piano'];
            $this->assertCount(24, $notes);
            $midi = array_map(fn (array $note): int => ($note['octave'] + 1) * 12 + array_search($note['degree'], $names, true), $notes);
            $this->assertSame([48 + $key, 52 + $key, 55 + $key, 48 + $key], array_slice($midi, 0, 4));
            $this->assertSame([0.0, 0.0, 0.0, 1.6, ...array_fill(0, 5, 3.2), ...array_fill(0, 5, 4.8), ...array_fill(0, 5, 6.4), ...array_fill(0, 5, 8.0)], array_column($notes, 'offset'));
            $previous = null;
            foreach (explode(' - ', $row['answer']) as $index => $label) {
                [$degree,$quality] = explode(' ', $label, 2);
                $chord = array_slice($midi, 4 + $index * 5, 5);
                $this->assertCount(5, array_unique($chord));
                $this->assertGreaterThan($chord[3], $chord[4]);
                $this->assertSame($chord[1] % 12, $chord[4] % 12);
                $this->assertGreaterThanOrEqual($chord[4] - 12, $chord[3]);
                $this->assertEqualsCanonicalizing(array_map(fn (int $interval): int => ($key + $scale[$degree] + $interval) % 12, $intervals[$quality]), array_values(array_unique(array_map(fn (int $pitch): int => $pitch % 12, $chord))));
                if ($previous !== null) {
                    foreach ($chord as $voice => $pitch) {
                        $this->assertLessThanOrEqual(4, abs($pitch - $previous[$voice]));
                    }
                }
                $previous = $chord;
            }
        }
        $this->assertNull(NoteSample::resolve('progression-build-two/c-major-v1/piano/invalid-p001-v0.wav'));
    }

    public function test_build_is_repeatable_preserves_other_quizzes_and_supports_preview_playback_and_grading(): void
    {
        $other = QuizQuestion::factory()->create();
        $before = $other->fresh()->getAttributes();
        $quiz = Quiz::factory()->create(['title' => 'Progression Build #2', 'category' => 'Chord Progressions']);
        $old = QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);
        $attempt = QuizAttempt::factory()->create(['quiz_id' => $quiz->id, 'question_sequence' => [$old->id]]);
        ob_start();
        try {
            require base_path('scripts/audio/build_progression_build_two.php');
            $ids = $quiz->questions()->pluck('id')->all();
            require base_path('scripts/audio/build_progression_build_two.php');
        } finally {
            ob_end_clean();
        }
        $this->assertCount(1080, $ids);
        $this->assertSame($ids, $quiz->questions()->pluck('id')->all());
        $this->assertSame(range(1, 1080), $quiz->questions()->pluck('position')->all());
        $this->assertSame($before, $other->fresh()->getAttributes());
        $this->assertDatabaseMissing('quiz_questions', ['id' => $old->id]);
        $this->assertDatabaseMissing('quiz_attempts', ['id' => $attempt->id]);
        $admin = $this->actingAs(User::factory()->admin()->create())->get(route('admin.audio-quiz.lesson', ['chord-progressions', 'progression-build-2']))->assertOk()->viewData('page')['props'];
        $this->assertCount(24, $admin['quiz']['questions'][0]['audio_variants'][0]['notes']);
        $this->actingAs(User::factory()->create())->post(route('quiz.start', $quiz), ['sound' => 'Piano'])->assertRedirect();
        $member = $this->get(route('quiz.play', $quiz))->assertOk()->viewData('page')['props'];
        $this->assertCount(24, $member['question']['notes']);
        $question = $quiz->questions()->whereKey($quiz->attempts()->firstOrFail()->questionIdAt(1))->firstOrFail();
        $this->post(route('quiz.answer', $quiz), ['choice' => $question->answer])->assertRedirect();
        $this->assertTrue($quiz->attempts()->firstOrFail()->answers()->firstOrFail()->correct);
    }
}
