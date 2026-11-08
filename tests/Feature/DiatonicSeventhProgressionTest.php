<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Support\DiatonicProgressionSamples;
use App\Support\DiatonicSeventhProgressionSamples;
use App\Support\NoteSample;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DiatonicSeventhProgressionTest extends TestCase
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

    public function test_enabled_major_keys_use_all_fifty_sequences_with_complete_sevenths_and_close_movement(): void
    {
        $rows = DiatonicSeventhProgressionSamples::manifest();
        $this->assertCount(2400, $rows);
        $this->assertSame(['c' => 200, 'db' => 200, 'd' => 200, 'eb' => 200, 'e' => 200, 'f' => 200, 'gb' => 200, 'g' => 200, 'ab' => 200, 'a' => 200, 'bb' => 200, 'b' => 200], array_count_values(array_column($rows, 'key')));
        $this->assertSame(DiatonicProgressionSamples::PATTERNS, DiatonicSeventhProgressionSamples::PATTERNS);
        $scale = [1 => 0, 2 => 2, 3 => 4, 4 => 5, 5 => 7, 6 => 9, 7 => 11];
        $intervals = [1 => [0, 4, 7, 11], 2 => [0, 3, 7, 10], 3 => [0, 3, 7, 10], 4 => [0, 4, 7, 11], 5 => [0, 4, 7, 10], 6 => [0, 3, 7, 10], 7 => [0, 3, 6, 10]];
        foreach ($rows as $row) {
            $this->assertContains($row['key'], ['c', 'db', 'd', 'eb', 'e', 'f', 'gb', 'g', 'ab', 'a', 'bb', 'b']);
            $key = array_search($row['key'], ['c', 'db', 'd', 'eb', 'e', 'f', 'gb', 'g', 'ab', 'a', 'bb', 'b'], true);
            $notes = $row['samples']['piano'];
            $this->assertCount(24, $notes);
            $names = ['Do', 'C#/Db', 'Re', 'D#/Eb', 'Mi', 'Fa', 'F#/Gb', 'So', 'G#/Ab', 'La', 'A#/Bb', 'Ti'];
            $midi = array_map(fn (array $note): int => ($note['octave'] + 1) * 12 + array_search($note['degree'], $names, true), $notes);
            $this->assertSame([$key, ($key + 4) % 12, ($key + 7) % 12, $key], array_map(fn (int $pitch): int => $pitch % 12, array_slice($midi, 0, 4)));
            $this->assertSame([48 + $key, 52 + $key, 55 + $key, 48 + $key], array_slice($midi, 0, 4));
            $previous = null;
            foreach ($row['degrees'] as $index => $degree) {
                $chord = array_slice($midi, 4 + $index * 5, 5);
                $this->assertCount(4, array_unique(array_map(fn (int $pitch): int => $pitch % 12, $chord)));
                $this->assertGreaterThan($chord[3], $chord[4]);
                $this->assertSame($chord[1] % 12, $chord[4] % 12);
                $this->assertGreaterThanOrEqual($chord[4] - 12, $chord[3]);
                $this->assertEqualsCanonicalizing(array_map(fn (int $interval): int => ($key + $scale[$degree] + $interval) % 12, $intervals[$degree]), array_map(fn (int $pitch): int => $pitch % 12, array_slice($chord, 0, 4)));
                if ($previous !== null) {
                    foreach ($chord as $voice => $pitch) {
                        $this->assertLessThanOrEqual(4, abs($pitch - $previous[$voice]));
                    }
                }
                $previous = $chord;
            }
            $this->assertSame(implode(' - ', array_map(fn (int $degree): string => $degree.' '.DiatonicSeventhProgressionSamples::QUALITIES[$degree], $row['degrees'])), $row['answer']);
        }
        $this->assertNull(NoteSample::resolve('diatonic-seventh-progressions/c-major-v1/piano/invalid-1564-v0.wav'));
    }

    public function test_build_preserves_the_triad_quiz_and_supports_preview_playback_and_grading(): void
    {
        $triad = Quiz::factory()->create(['title' => 'Diatonic Progression #1', 'category' => 'Chord Progressions']);
        $original = QuizQuestion::factory()->create(['quiz_id' => $triad->id]);
        $before = $original->fresh()->getAttributes();
        ob_start();
        try {
            require base_path('scripts/audio/build_diatonic_seventh_progressions.php');
            $quiz = Quiz::where('title', 'Diatonic Progression #2')->firstOrFail();
            $ids = $quiz->questions()->pluck('id')->all();
            require base_path('scripts/audio/build_diatonic_seventh_progressions.php');
        } finally {
            ob_end_clean();
        }
        $this->assertCount(2400, $ids);
        $this->assertSame($ids, $quiz->questions()->pluck('id')->all());
        $this->assertSame($before, $original->fresh()->getAttributes());
        $this->assertTrue($quiz->fresh()->is_published);
        $question = $quiz->questions()->firstOrFail();
        $notes = NoteSample::resolve($question->audioVariants->first()->audio_path);
        $this->assertCount(24, $notes);
        $admin = $this->actingAs(User::factory()->admin()->create())->get(route('admin.audio-quiz.lesson', ['chord-progressions', 'diatonic-progression-2']))->assertOk()->viewData('page')['props'];
        $this->assertCount(24, $admin['quiz']['questions'][0]['audio_variants'][0]['notes']);
        $this->actingAs(User::factory()->create())->post(route('quiz.start', $quiz), ['sound' => 'Piano'])->assertRedirect();
        $member = $this->get(route('quiz.play', $quiz))->assertOk()->viewData('page')['props'];
        $this->assertCount(24, $member['question']['notes']);
        $this->post(route('quiz.answer', $quiz), ['choice' => $quiz->questions()->whereKey($quiz->attempts()->firstOrFail()->questionIdAt(1))->firstOrFail()->answer])->assertRedirect();
        $this->assertTrue($quiz->attempts()->firstOrFail()->answers()->firstOrFail()->correct);
    }
}
