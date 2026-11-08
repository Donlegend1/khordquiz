<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\User;
use App\Support\MajorModeVoicingSamples;
use App\Support\MobileCatalog;
use App\Support\ModalVoicingLessons;
use App\Support\NoteSample;
use App\Support\QuizAnswerPresets;
use App\Support\QuizCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MajorModeVoicingLiveMixTest extends TestCase
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
            $register = MajorModeVoicingSamples::preferredRegister($slug);
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

    public function test_six_seven_note_voicings_match_the_agreed_upper_chords(): void
    {
        $expected = [
            'romanian-major' => ['Romanian Major', [48, 52, 55, 57, 58, 61, 66]],
            'hungarian-major' => ['Hungarian Major', [48, 52, 55, 57, 58, 63, 66]],
            'byzantine-major' => ['Byzantine Major', [48, 52, 55, 56, 59, 61, 65]],
            'harmonic-major' => ['Harmonic Major', [48, 52, 55, 56, 59, 62, 65]],
            'melodic-major' => ['Melodic Major', [48, 52, 55, 56, 58, 62, 65]],
            'major-augmented' => ['Major Augmented', [48, 52, 56, 57, 59, 62, 65]],
        ];
        $upperChords = [
            'romanian-major' => [4, [0, 2, 3, 5, 6, 9]],
            'hungarian-major' => [4, [0, 2, 3, 5, 6, 11]],
            'byzantine-major' => [1, [0, 3, 4, 6, 7, 10]],
            'harmonic-major' => [7, [0, 1, 4, 7, 9, 10]],
            'melodic-major' => [7, [0, 1, 3, 7, 9, 10]],
            'major-augmented' => [4, [0, 1, 4, 5, 7, 10]],
        ];
        $this->assertSame($expected, MajorModeVoicingSamples::VOICINGS);
        $this->assertSame(array_column($expected, 0), MajorModeVoicingSamples::choices());
        foreach ($expected as $id => [$answer, $shape]) {
            $this->assertSame([48, 52], array_slice($shape, 0, 2));
            [$root, $intervals] = $upperChords[$id];
            $this->assertEqualsCanonicalizing($intervals, array_map(fn (int $pitch): int => ($pitch - $root) % 12, array_slice($shape, 1)));
        }
        $manifest = MajorModeVoicingSamples::manifest();
        $this->assertCount(6, $manifest);
        $this->assertSame(array_fill(0, 6, 'c'), array_column($manifest, 'key'));
        $this->assertCount(25, QuizCatalog::soundsFor('Modal Voicings', MajorModeVoicingSamples::TITLE));
        foreach ($manifest as $row) {
            $this->assertCount(25, $row['samples']);
            [$answer, $shape] = MajorModeVoicingSamples::VOICINGS[substr($row['recipe'], 2)];
            $this->assertSame($answer, $row['answer']);
            foreach ($row['samples'] as $slug => $notes) {
                $this->assertCount(7, $notes);
                $this->assertSame(array_fill(0, 7, 0.0), array_column($notes, 'offset'));
                [$low, $high] = MajorModeVoicingSamples::preferredRegister($slug);
                $matched = false;
                foreach (range(-48, 48, 12) as $shift) {
                    if ($low > $shape[0] + $shift || $high < $shape[6] + $shift) {
                        continue;
                    }
                    $expected = array_map(fn (int $pitch): ?array => NoteSample::fromRecordedMidiPitch($pitch + $shift, $slug), $shape);
                    if ($expected === $notes) {
                        $matched = true;
                    }
                }
                $this->assertTrue($matched, $slug.'/'.$row['recipe']);
                $resolved = NoteSample::resolve(MajorModeVoicingSamples::PREFIX.'/'.$slug.'/'.$row['recipe'].'.wav');
                $this->assertSame(array_map(fn (array $note): array => ['slug' => $slug, ...$note], $notes), $resolved);
            }
        }
    }

    public function test_each_voicing_fits_only_its_intended_major_mode(): void
    {
        $scales = [
            'Romanian Major' => [0, 1, 4, 6, 7, 9, 10],
            'Hungarian Major' => [0, 3, 4, 6, 7, 9, 10],
            'Byzantine Major' => [0, 1, 4, 5, 7, 8, 11],
            'Harmonic Major' => [0, 2, 4, 5, 7, 8, 11],
            'Melodic Major' => [0, 2, 4, 5, 7, 8, 10],
            'Major Augmented' => [0, 2, 4, 5, 8, 9, 11],
        ];
        foreach (MajorModeVoicingSamples::VOICINGS as [$answer, $shape]) {
            $tones = array_map(fn (int $pitch): int => $pitch % 12, $shape);
            $matches = array_keys(array_filter($scales, fn (array $scale): bool => array_diff($tones, $scale) === []));
            $this->assertSame([$answer], $matches);
        }
    }

    public function test_every_mode_uses_the_same_c_bass_octave_for_each_instrument(): void
    {
        $bassByInstrument = [];
        foreach (MajorModeVoicingSamples::manifest() as $row) {
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
        foreach ([48, 52, 55, 57, 58, 61] as $pitch) {
            Storage::disk('public')->put(NoteSample::path('piano', self::NAMES[$pitch % 12], intdiv($pitch, 12) - 1), 'recording');
        }
        $this->assertNull(MajorModeVoicingSamples::resolve(MajorModeVoicingSamples::PREFIX.'/piano/c-romanian-major.wav'));
        $this->assertNull(MajorModeVoicingSamples::resolve(MajorModeVoicingSamples::PREFIX.'/tuba/c-romanian-major.wav'));
        $this->assertNull(MajorModeVoicingSamples::resolve(MajorModeVoicingSamples::PREFIX.'/piano/db-romanian-major.wav'));
        $this->assertNull(MajorModeVoicingSamples::resolve('other/piano/db-major-second.wav'));
    }

    public function test_six_variant_session_is_balanced_and_randomized(): void
    {
        $quiz = Quiz::factory()->create(['title' => MajorModeVoicingSamples::TITLE, 'category' => 'Modal Voicings', 'question_count' => 25]);
        foreach (MajorModeVoicingSamples::choices() as $index => $answer) {
            $quiz->questions()->create(['position' => $index + 1, 'prompt' => 'Listen', 'answer' => $answer, 'choices' => MajorModeVoicingSamples::choices()]);
        }
        mt_srand(2026);
        try {
            $this->actingAs(User::factory()->create())->post(route('quiz.start', $quiz))->assertRedirect();
        } finally {
            mt_srand();
        }
        $sequence = $quiz->attempts()->firstOrFail()->question_sequence;
        $this->assertCount(25, $sequence);
        $this->assertEqualsCanonicalizing([4, 4, 4, 4, 4, 5], array_values(array_count_values($sequence)));
        $hasRepeatedNeighbor = false;
        foreach (array_slice($sequence, 1) as $index => $id) {
            $hasRepeatedNeighbor = $hasRepeatedNeighbor || $id === $sequence[$index];
        }
        $this->assertTrue($hasRepeatedNeighbor);
    }

    public function test_family_has_f_only_and_twelve_key_lessons_in_the_catalog(): void
    {
        $titles = ModalVoicingLessons::titles();
        $this->assertSame(MajorModeVoicingSamples::TITLE.' #1', $titles[14]);
        $this->assertSame(MajorModeVoicingSamples::TITLE.' #2', $titles[15]);
        $this->assertSame($titles, QuizCatalog::all()['Modal Voicings']);
        $this->assertSame(MajorModeVoicingSamples::choices(), QuizAnswerPresets::presetFor('Modal Voicings', $titles[14]));
        $this->assertSame(MajorModeVoicingSamples::choices(), QuizAnswerPresets::presetFor('Modal Voicings', $titles[15]));
        $this->assertSame($titles, array_column(MobileCatalog::quizzes('Modal Voicings'), 'name'));
    }

    public function test_builder_creates_a_new_advanced_modal_quiz_without_reusing_the_scale_quiz(): void
    {
        $scale = Quiz::factory()->create(['title' => MajorModeVoicingSamples::TITLE, 'category' => 'Scales', 'slug' => 'major-modes']);
        $scale->questions()->create(['position' => 1, 'prompt' => 'Keep scale', 'answer' => 'Keep scale', 'choices' => []]);
        $before = $scale->fresh()->load('questions')->toJson();
        ob_start();
        require base_path('scripts/audio/build_major_mode_voicings.php');
        ob_end_clean();
        $quiz = Quiz::where('category', 'Modal Voicings')->where('title', MajorModeVoicingSamples::TITLE)->firstOrFail();
        $this->assertNotSame($scale->id, $quiz->id);
        $this->assertSame('modal-voicings-major-modes', $quiz->slug);
        $this->assertSame('Advanced', $quiz->difficulty);
        $this->assertTrue($quiz->is_published);
        $this->assertSame(25, $quiz->question_count);
        $this->assertSame(6, $quiz->questions()->count());
        $this->assertSame($before, $scale->fresh()->load('questions')->toJson());
    }

    public function test_rebuild_only_replaces_the_major_modes_lesson(): void
    {
        $quiz = Quiz::factory()->create(['title' => MajorModeVoicingSamples::LEGACY_TITLE, 'category' => 'Modal Voicings', 'slug' => 'modal-voicings-major-modes']);
        $old = $quiz->questions()->create(['position' => 1, 'prompt' => 'Old', 'answer' => 'Old', 'choices' => []]);
        $other = Quiz::factory()->create(['title' => 'Lydian Mode', 'category' => 'Modal Voicings']);
        $other->questions()->create(['position' => 1, 'prompt' => 'Keep', 'answer' => 'Keep', 'choices' => []]);
        $before = $other->fresh()->load('questions')->toJson();
        $ionian = Quiz::factory()->create(['title' => 'Ionian Mode', 'category' => 'Modal Voicings']);
        $ionian->questions()->create(['position' => 1, 'prompt' => 'Keep Ionian', 'answer' => 'Keep', 'choices' => []]);
        $ionianBefore = $ionian->fresh()->load('questions')->toJson();
        $aeolian = Quiz::factory()->create(['title' => 'Aeolian Mode', 'category' => 'Modal Voicings']);
        $aeolian->questions()->create(['position' => 1, 'prompt' => 'Keep Aeolian', 'answer' => 'Keep', 'choices' => []]);
        $aeolianBefore = $aeolian->fresh()->load('questions')->toJson();
        $dorian = Quiz::factory()->create(['title' => 'Dorian Mode', 'category' => 'Modal Voicings']);
        $dorian->questions()->create(['position' => 1, 'prompt' => 'Keep Dorian', 'answer' => 'Keep', 'choices' => []]);
        $dorianBefore = $dorian->fresh()->load('questions')->toJson();
        $locrian = Quiz::factory()->create(['title' => 'Locrian Mode', 'category' => 'Modal Voicings']);
        $locrian->questions()->create(['position' => 1, 'prompt' => 'Keep Locrian', 'answer' => 'Keep', 'choices' => []]);
        $locrianBefore = $locrian->fresh()->load('questions')->toJson();
        $phrygian = Quiz::factory()->create(['title' => 'Phrygian Mode', 'category' => 'Modal Voicings']);
        $phrygian->questions()->create(['position' => 1, 'prompt' => 'Keep Phrygian', 'answer' => 'Keep', 'choices' => []]);
        $phrygianBefore = $phrygian->fresh()->load('questions')->toJson();
        $mixolydian = Quiz::factory()->create(['title' => 'Mixolydian Mode', 'category' => 'Modal Voicings']);
        $mixolydian->questions()->create(['position' => 1, 'prompt' => 'Keep Mixolydian', 'answer' => 'Keep', 'choices' => []]);
        $mixolydianBefore = $mixolydian->fresh()->load('questions')->toJson();
        $scale = Quiz::factory()->create(['title' => MajorModeVoicingSamples::TITLE, 'category' => 'Scales']);
        $scale->questions()->create(['position' => 1, 'prompt' => 'Keep scale', 'answer' => 'Keep scale', 'choices' => []]);
        $scaleBefore = $scale->fresh()->load('questions')->toJson();
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('quiz.start', $quiz))->assertRedirect();
        $this->assertSame(1, $quiz->attempts()->count());
        ob_start();
        require base_path('scripts/audio/build_major_mode_voicings.php');
        ob_end_clean();
        $this->assertSame(0, $quiz->attempts()->count());
        $this->assertSame(6, $quiz->questions()->count());
        $this->assertSame(MajorModeVoicingSamples::TITLE, $quiz->fresh()->title);
        $this->assertSame(25, $quiz->fresh()->question_count);
        $this->assertSame('modal-voicings-major-modes', $quiz->fresh()->slug);
        $this->assertDatabaseMissing('quiz_questions', ['id' => $old->id]);
        $this->assertSame($before, $other->fresh()->load('questions')->toJson());
        $this->assertSame($ionianBefore, $ionian->fresh()->load('questions')->toJson());
        $this->assertSame($aeolianBefore, $aeolian->fresh()->load('questions')->toJson());
        $this->assertSame($dorianBefore, $dorian->fresh()->load('questions')->toJson());
        $this->assertSame($locrianBefore, $locrian->fresh()->load('questions')->toJson());
        $this->assertSame($phrygianBefore, $phrygian->fresh()->load('questions')->toJson());
        $this->assertSame($mixolydianBefore, $mixolydian->fresh()->load('questions')->toJson());
        $this->assertSame($scaleBefore, $scale->fresh()->load('questions')->toJson());
        foreach ($quiz->questions()->get() as $question) {
            $this->assertSame(MajorModeVoicingSamples::choices(), $question->choices);
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
