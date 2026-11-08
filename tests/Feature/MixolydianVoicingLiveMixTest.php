<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\User;
use App\Support\MixolydianVoicingSamples;
use App\Support\MobileCatalog;
use App\Support\ModalVoicingLessons;
use App\Support\NoteSample;
use App\Support\QuizAnswerPresets;
use App\Support\QuizCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MixolydianVoicingLiveMixTest extends TestCase
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
            $register = MixolydianVoicingSamples::preferredRegister($slug);
            if ($register === null) {
                continue;
            }
            foreach (range($register[0], $register[1]) as $pitch) {
                $name = self::NAMES[$pitch % 12];
                $octave = intdiv($pitch, 12) - 1 + (in_array($slug, ['organ', 'harpejji'], true) ? 1 : 0);
                if (str_contains($name, '#') && ! in_array($slug, [...$full, 'celesta', 'vibraphone'], true)) {
                    $octave--;
                }
                Storage::disk('public')->put(NoteSample::path($slug, $name, $octave), 'recording');
            }
        }
    }

    public function test_five_six_note_voicings_preserve_the_shell_and_only_change_the_agreed_tones(): void
    {
        $expected = [
            'mixolydian' => ['Mixolydian', [48, 52, 57, 58, 62, 65]],
            'mixolydian-flat-two' => ['Mixolydian ♭2', [48, 52, 57, 58, 61, 65]],
            'mixolydian-flat-six' => ['Mixolydian ♭6', [48, 52, 56, 58, 62, 65]],
            'mixolydian-flat-two-flat-six' => ['Mixolydian ♭2 ♭6', [48, 52, 56, 58, 61, 65]],
            'mixolydian-sharp-four' => ['Mixolydian ♯4', [48, 52, 57, 58, 62, 66]],
        ];
        $this->assertSame($expected, MixolydianVoicingSamples::VOICINGS);
        $this->assertSame(array_column($expected, 0), MixolydianVoicingSamples::choices());
        $base = $expected['mixolydian'][1];
        foreach ($expected as $id => [$answer, $shape]) {
            $changed = array_keys(array_filter($shape, fn (int $pitch, int $index): bool => $pitch !== $base[$index], ARRAY_FILTER_USE_BOTH));
            $this->assertCount($id === 'mixolydian' ? 0 : ($id === 'mixolydian-flat-two-flat-six' ? 2 : 1), $changed);
            $this->assertSame([48, 52, 58], [$shape[0], $shape[1], $shape[3]]);
            foreach ($changed as $index) {
                $this->assertSame(1, abs($shape[$index] - $base[$index]));
            }
        }
        $manifest = MixolydianVoicingSamples::manifest();
        $this->assertCount(5, $manifest);
        $this->assertSame(array_fill(0, 5, 'c'), array_column($manifest, 'key'));
        $this->assertCount(25, QuizCatalog::soundsFor('Modal Voicings', MixolydianVoicingSamples::TITLE));
        foreach ($manifest as $row) {
            $this->assertCount(25, $row['samples']);
            [$answer, $shape] = MixolydianVoicingSamples::VOICINGS[substr($row['recipe'], 2)];
            $this->assertSame($answer, $row['answer']);
            foreach ($row['samples'] as $slug => $notes) {
                $this->assertCount(6, $notes);
                $this->assertSame(array_fill(0, 6, 0.0), array_column($notes, 'offset'));
                [$low, $high] = MixolydianVoicingSamples::preferredRegister($slug);
                $matched = false;
                foreach (range(-48, 48, 12) as $shift) {
                    if ($low > $shape[0] + $shift || $high < $shape[5] + $shift) {
                        continue;
                    }
                    $expected = array_map(fn (int $pitch): ?array => NoteSample::fromRecordedMidiPitch($pitch + $shift, $slug), $shape);
                    if ($expected === $notes) {
                        $matched = true;
                    }
                }
                $this->assertTrue($matched, $slug.'/'.$row['recipe']);
                $resolved = NoteSample::resolve(MixolydianVoicingSamples::PREFIX.'/'.$slug.'/'.$row['recipe'].'.wav');
                $this->assertSame(array_map(fn (array $note): array => ['slug' => $slug, ...$note], $notes), $resolved);
            }
        }
    }

    public function test_each_voicing_fits_only_its_intended_mixolydian_variant(): void
    {
        $scales = [
            'Mixolydian' => [0, 2, 4, 5, 7, 9, 10],
            'Mixolydian ♭2' => [0, 1, 4, 5, 7, 9, 10],
            'Mixolydian ♭6' => [0, 2, 4, 5, 7, 8, 10],
            'Mixolydian ♭2 ♭6' => [0, 1, 4, 5, 7, 8, 10],
            'Mixolydian ♯4' => [0, 2, 4, 6, 7, 9, 10],
        ];
        foreach (MixolydianVoicingSamples::VOICINGS as [$answer, $shape]) {
            $tones = array_map(fn (int $pitch): int => $pitch % 12, $shape);
            $matches = array_keys(array_filter($scales, fn (array $scale): bool => array_diff($tones, $scale) === []));
            $this->assertSame([$answer], $matches);
        }
    }

    public function test_every_mode_uses_the_same_c_bass_octave_for_each_instrument(): void
    {
        $bassByInstrument = [];
        foreach (MixolydianVoicingSamples::manifest() as $row) {
            foreach ($row['samples'] as $slug => $notes) {
                $bassByInstrument[$row['key']][$slug] ??= $notes[0];
                $this->assertSame($bassByInstrument[$row['key']][$slug], $notes[0], $slug.'/'.$row['recipe']);
                $this->assertSame('Do', $notes[0]['degree']);
            }
        }
        $this->assertSame(['degree' => 'Do', 'octave' => 3, 'offset' => 0.0], $bassByInstrument['c']['piano']);
    }

    public function test_missing_recordings_exclude_the_whole_voicing_without_dropping_or_moving_a_tone(): void
    {
        Storage::fake('public');
        Cache::flush();
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
        foreach ([48, 52, 55, 59, 62] as $pitch) {
            Storage::disk('public')->put(NoteSample::path('piano', self::NAMES[$pitch % 12], intdiv($pitch, 12) - 1), 'recording');
        }
        $this->assertNull(MixolydianVoicingSamples::resolve(MixolydianVoicingSamples::PREFIX.'/piano/c-mixolydian.wav'));
        $this->assertNull(MixolydianVoicingSamples::resolve(MixolydianVoicingSamples::PREFIX.'/tuba/c-mixolydian.wav'));
        $this->assertNull(MixolydianVoicingSamples::resolve(MixolydianVoicingSamples::PREFIX.'/piano/db-mixolydian.wav'));
        $this->assertNull(MixolydianVoicingSamples::resolve('other/piano/db-major-second.wav'));
    }

    public function test_five_variant_session_is_balanced_and_randomized(): void
    {
        $quiz = Quiz::factory()->create(['title' => MixolydianVoicingSamples::TITLE, 'category' => 'Modal Voicings', 'question_count' => 25]);
        foreach (MixolydianVoicingSamples::choices() as $index => $answer) {
            $quiz->questions()->create(['position' => $index + 1, 'prompt' => 'Listen', 'answer' => $answer, 'choices' => MixolydianVoicingSamples::choices()]);
        }
        mt_srand(2026);
        try {
            $this->actingAs(User::factory()->create())->post(route('quiz.start', $quiz))->assertRedirect();
        } finally {
            mt_srand();
        }
        $sequence = $quiz->attempts()->firstOrFail()->question_sequence;
        $this->assertCount(25, $sequence);
        $this->assertEqualsCanonicalizing([5, 5, 5, 5, 5], array_values(array_count_values($sequence)));
        $hasRepeatedNeighbor = false;
        foreach (array_slice($sequence, 1) as $index => $id) {
            $hasRepeatedNeighbor = $hasRepeatedNeighbor || $id === $sequence[$index];
        }
        $this->assertTrue($hasRepeatedNeighbor);
    }

    public function test_family_has_f_only_and_twelve_key_lessons_in_the_catalog(): void
    {
        $titles = ModalVoicingLessons::titles();
        $this->assertSame(MixolydianVoicingSamples::TITLE.' #1', $titles[2]);
        $this->assertSame(MixolydianVoicingSamples::TITLE.' #2', $titles[3]);
        $this->assertSame($titles, QuizCatalog::all()['Modal Voicings']);
        $this->assertSame(MixolydianVoicingSamples::choices(), QuizAnswerPresets::presetFor('Modal Voicings', $titles[2]));
        $this->assertSame(MixolydianVoicingSamples::choices(), QuizAnswerPresets::presetFor('Modal Voicings', $titles[3]));
        $this->assertSame($titles, array_column(MobileCatalog::quizzes('Modal Voicings'), 'name'));
    }

    public function test_rebuild_only_renames_and_replaces_the_second_modal_lesson(): void
    {
        $quiz = Quiz::factory()->create(['title' => MixolydianVoicingSamples::LEGACY_TITLE, 'category' => 'Modal Voicings', 'slug' => 'modal-voicings-level-2']);
        $old = $quiz->questions()->create(['position' => 1, 'prompt' => 'Old', 'answer' => 'Old', 'choices' => []]);
        $other = Quiz::factory()->create(['title' => 'Lydian Mode', 'category' => 'Modal Voicings']);
        $other->questions()->create(['position' => 1, 'prompt' => 'Keep', 'answer' => 'Keep', 'choices' => []]);
        $before = $other->fresh()->load('questions')->toJson();
        $scale = Quiz::factory()->create(['title' => MixolydianVoicingSamples::TITLE, 'category' => 'Scales']);
        $scale->questions()->create(['position' => 1, 'prompt' => 'Keep scale', 'answer' => 'Keep scale', 'choices' => []]);
        $scaleBefore = $scale->fresh()->load('questions')->toJson();
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('quiz.start', $quiz))->assertRedirect();
        $this->assertSame(1, $quiz->attempts()->count());
        ob_start();
        require base_path('scripts/audio/build_mixolydian_voicings.php');
        ob_end_clean();
        $this->assertSame(0, $quiz->attempts()->count());
        $this->assertSame(5, $quiz->questions()->count());
        $this->assertSame(MixolydianVoicingSamples::TITLE, $quiz->fresh()->title);
        $this->assertSame(25, $quiz->fresh()->question_count);
        $this->assertSame('modal-voicings-level-2', $quiz->fresh()->slug);
        $this->assertDatabaseMissing('quiz_questions', ['id' => $old->id]);
        $this->assertSame($before, $other->fresh()->load('questions')->toJson());
        $this->assertSame($scaleBefore, $scale->fresh()->load('questions')->toJson());
        foreach ($quiz->questions()->get() as $question) {
            $this->assertSame(MixolydianVoicingSamples::choices(), $question->choices);
            $this->assertSame(25, $question->audioVariants()->count());
        }
        $quiz->update(['question_count' => 1]);
        $quiz->questions()->where('position', '!=', 1)->delete();
        $answer = $quiz->questions()->firstOrFail()->answer;
        $this->actingAs($user)->post(route('quiz.start', $quiz))->assertRedirect();
        $this->post(route('quiz.answer', $quiz), ['choice' => $answer])->assertRedirect();
        $this->assertSame(1, $quiz->attempts()->firstOrFail()->correct_answers);
    }
}
