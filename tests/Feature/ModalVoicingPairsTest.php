<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Support\MinorModeVoicingSamples;
use App\Support\MobileCatalog;
use App\Support\ModalVoicingLessons;
use App\Support\ModalVoicingSamples;
use App\Support\NoteSample;
use App\Support\QuizAnswerPresets;
use App\Support\QuizCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ModalVoicingPairsTest extends TestCase
{
    use RefreshDatabase;

    private const NAMES = ['Do', 'C#/Db', 'Re', 'D#/Eb', 'Mi', 'Fa', 'F#/Gb', 'So', 'G#/Ab', 'La', 'A#/Bb', 'Ti'];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Cache::flush();
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
        $full = (new \ReflectionClass(NoteSample::class))->getConstant('FULL_CHROMATIC_BANK_SLUGS');
        foreach (QuizCatalog::SOUND_SLUGS as $slug) {
            $register = MinorModeVoicingSamples::preferredRegister($slug);
            if ($register === null) {
                continue;
            }
            foreach (range($register[0] - 6, $register[1] + 6) as $pitch) {
                $name = self::NAMES[$pitch % 12];
                $octave = intdiv($pitch, 12) - 1 + (in_array($slug, ['organ', 'harpejji'], true) ? 1 : 0);
                if (str_contains($name, '#') && ! in_array($slug, [...$full, 'celesta', 'vibraphone'], true)) {
                    $octave--;
                }
                Storage::disk('public')->put(NoteSample::path($slug, $name, $octave), 'recording');
            }
        }
    }

    public function test_all_eighteen_lessons_transpose_exact_shapes_and_keep_common_bass_octaves(): void
    {
        $this->assertCount(18, ModalVoicingLessons::titles());
        $this->assertSame(ModalVoicingLessons::titles(), QuizCatalog::subcategories('Modal Voicings'));
        $this->assertSame(ModalVoicingLessons::titles(), array_column(MobileCatalog::quizzes('Modal Voicings'), 'name'));
        foreach (ModalVoicingLessons::lessons() as $lesson) {
            $source = ModalVoicingLessons::FAMILIES[$lesson['family']];
            $rows = ModalVoicingLessons::manifest($lesson['family'], $lesson['number']);
            $this->assertCount(count($source::choices()) * ($lesson['number'] === 1 ? 1 : 12), $rows);
            $this->assertEqualsCanonicalizing($lesson['number'] === 1 ? ['f'] : array_keys(ModalVoicingLessons::KEYS), array_values(array_unique(array_column($rows, 'key'))));
            $this->assertSame($source::choices(), QuizAnswerPresets::presetFor('Modal Voicings', $lesson['title']));
            $this->assertCount(count(ModalVoicingLessons::sounds($lesson['title'])), MobileCatalog::sounds(MobileCatalog::slug($lesson['title']), 'Modal Voicings'));
            $bass = [];
            foreach ($rows as $row) {
                $this->assertCount(count(ModalVoicingLessons::sounds($lesson['title'])), $row['samples']);
                $id = substr($row['recipe'], strlen($row['key']) + 1);
                [$answer, $shape] = $source::VOICINGS[$id];
                $this->assertSame($answer, $row['answer']);
                foreach ($row['samples'] as $slug => $notes) {
                    $bass[$row['key']][$slug] ??= $notes[0];
                    $this->assertSame($bass[$row['key']][$slug], $notes[0]);
                    $this->assertCount(count($shape), $notes);
                    $this->assertSame(array_fill(0, count($shape), 0.0), array_column($notes, 'offset'));
                    [$low, $high] = ModalVoicingSamples::preferredRegister($slug);
                    $matched = false;
                    foreach (range(-48, 48, 12) as $shift) {
                        $transposed = array_map(fn (int $p): int => $p + ModalVoicingLessons::KEYS[$row['key']] + $shift, $shape);
                        if (min($transposed) < $low - 6 || max($transposed) > $high + 6) {
                            continue;
                        }
                        $expected = array_map(fn (int $p): ?array => NoteSample::fromRecordedMidiPitch($p, $slug), $transposed);
                        $matched = $matched || $notes === $expected;
                    }
                    $this->assertTrue($matched, $lesson['title'].'/'.$row['recipe'].'/'.$slug);
                    $this->assertSame(array_map(fn (array $n): array => ['slug' => $slug, ...$n], $notes), NoteSample::resolve(ModalVoicingLessons::PREFIX.'/'.$lesson['family'].'/'.$slug.'/'.$row['recipe'].'.wav'));
                }
            }
            if ($lesson['number'] === 2 && $lesson['family'] !== 'aeolian') {
                $this->assertSame(ModalVoicingLessons::manifest($lesson['family'], 1), array_values(array_filter($rows, fn (array $r): bool => $r['key'] === 'f')));
            }
        }
        $this->assertNull(ModalVoicingLessons::resolve(ModalVoicingLessons::PREFIX.'/lydian/tuba/f-lydian.wav'));
        $this->assertNull(ModalVoicingLessons::resolve(ModalVoicingLessons::PREFIX.'/lydian/piano/invalid-lydian.wav'));
    }

    public function test_builder_reuses_existing_quizzes_creates_pairs_and_preserves_other_categories(): void
    {
        $oldIds = [];
        foreach (ModalVoicingLessons::FAMILIES as $family => $source) {
            $quiz = Quiz::factory()->create(['category' => 'Modal Voicings', 'title' => $source::TITLE]);
            $oldIds[$family] = $quiz->id;
            $quiz->questions()->create(['position' => 1, 'prompt' => 'Old C', 'answer' => 'Old C', 'choices' => ['Old C']]);
        }
        $scales = Quiz::factory()->create(['category' => 'Scales', 'title' => 'Lydian Mode']);
        $scales->questions()->create(['position' => 1, 'prompt' => 'Keep', 'answer' => 'Keep', 'choices' => ['Keep']]);
        $before = $scales->fresh()->load('questions')->toJson();
        ob_start();
        try {
            require base_path('scripts/audio/build_modal_voicing_pairs.php');
        } finally {
            ob_end_clean();
        }
        $this->assertSame(18, Quiz::where('category', 'Modal Voicings')->count());
        $this->assertSame($before, $scales->fresh()->load('questions')->toJson());
        foreach (ModalVoicingLessons::lessons() as $lesson) {
            $quiz = Quiz::where('category', 'Modal Voicings')->where('title', $lesson['title'])->firstOrFail();
            if ($lesson['number'] === 1) {
                $this->assertSame($oldIds[$lesson['family']], $quiz->id);
            }
            $this->assertSame(25, $quiz->question_count);
            $this->assertTrue($quiz->is_published);
            $this->assertSame('Advanced', $quiz->difficulty);
            $this->assertSame(count($lesson['choices']) * ($lesson['number'] === 1 ? 1 : 12), $quiz->questions()->count());
            foreach ($quiz->questions()->with('audioVariants')->get() as $question) {
                $this->assertSame($lesson['choices'], $question->choices);
                $this->assertCount(count(ModalVoicingLessons::sounds($lesson['title'])), $question->audioVariants);
            }
            $this->actingAs(User::factory()->create())->post(route('quiz.start', $quiz))->assertRedirect();
            $this->assertCount(25, $quiz->attempts()->firstOrFail()->question_sequence);
            $answer = $quiz->questions()->findOrFail($quiz->attempts()->firstOrFail()->question_sequence[0])->answer;
            $this->post(route('quiz.answer', $quiz), ['choice' => $answer])->assertRedirect();
            $this->assertSame(1, $quiz->attempts()->firstOrFail()->correct_answers);
        }
        $ids = Quiz::where('category', 'Modal Voicings')->orderBy('id')->pluck('id')->all();
        ob_start();
        try {
            require base_path('scripts/audio/build_modal_voicing_pairs.php');
        } finally {
            ob_end_clean();
        }
        $this->assertSame($ids, Quiz::where('category', 'Modal Voicings')->orderBy('id')->pluck('id')->all());
        $this->assertSame(0, QuizAttempt::whereIn('quiz_id', $ids)->count());
    }
}
