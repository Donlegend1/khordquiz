<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\User;
use App\Support\MinorModeVoicingSamples;
use App\Support\MobileCatalog;
use App\Support\ModalVoicingLessons;
use App\Support\NoteSample;
use App\Support\QuizAnswerPresets;
use App\Support\QuizCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MinorModeVoicingLiveMixTest extends TestCase
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
            'natural-minor' => ['Natural Minor', [48, 51, 55, 56, 58, 62, 65]],
            'harmonic-minor' => ['Harmonic Minor', [48, 51, 55, 56, 59, 62, 65]],
            'melodic-minor' => ['Melodic Minor', [48, 51, 55, 57, 59, 62, 65]],
            'hungarian-minor' => ['Hungarian Minor', [48, 51, 55, 56, 59, 62, 66]],
            'romanian-minor' => ['Romanian Minor', [48, 51, 55, 57, 58, 62, 66]],
            'neapolitan-minor' => ['Neapolitan Minor', [48, 51, 55, 56, 59, 61, 65]],
        ];
        $this->assertSame($expected, MinorModeVoicingSamples::VOICINGS);
        $this->assertSame(array_column($expected, 0), MinorModeVoicingSamples::choices());
        foreach ($expected as [$answer, $shape]) {
            $this->assertSame([48, 51], array_slice($shape, 0, 2));
        }
        $manifest = MinorModeVoicingSamples::manifest();
        $this->assertCount(6, $manifest);
        $this->assertSame(array_fill(0, 6, 'c'), array_column($manifest, 'key'));
        $this->assertCount(25, QuizCatalog::soundsFor('Modal Voicings', MinorModeVoicingSamples::TITLE));
        foreach ($manifest as $row) {
            $this->assertCount(25, $row['samples']);
            [$answer, $shape] = MinorModeVoicingSamples::VOICINGS[substr($row['recipe'], 2)];
            $this->assertSame($answer, $row['answer']);
            foreach ($row['samples'] as $slug => $notes) {
                $this->assertCount(7, $notes);
                $this->assertSame(array_fill(0, 7, 0.0), array_column($notes, 'offset'));
                [$low, $high] = MinorModeVoicingSamples::preferredRegister($slug);
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
                $resolved = NoteSample::resolve(MinorModeVoicingSamples::PREFIX.'/'.$slug.'/'.$row['recipe'].'.wav');
                $this->assertSame(array_map(fn (array $note): array => ['slug' => $slug, ...$note], $notes), $resolved);
            }
        }
    }

    public function test_each_voicing_fits_only_its_intended_minor_mode(): void
    {
        $scales = [
            'Natural Minor' => [0, 2, 3, 5, 7, 8, 10],
            'Harmonic Minor' => [0, 2, 3, 5, 7, 8, 11],
            'Melodic Minor' => [0, 2, 3, 5, 7, 9, 11],
            'Hungarian Minor' => [0, 2, 3, 6, 7, 8, 11],
            'Romanian Minor' => [0, 2, 3, 6, 7, 9, 10],
            'Neapolitan Minor' => [0, 1, 3, 5, 7, 8, 11],
        ];
        foreach (MinorModeVoicingSamples::VOICINGS as [$answer, $shape]) {
            $tones = array_map(fn (int $pitch): int => $pitch % 12, $shape);
            $matches = array_keys(array_filter($scales, fn (array $scale): bool => array_diff($tones, $scale) === []));
            $this->assertSame([$answer], $matches);
        }
    }

    public function test_every_mode_uses_the_same_c_bass_octave_for_each_instrument(): void
    {
        $bassByInstrument = [];
        foreach (MinorModeVoicingSamples::manifest() as $row) {
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
        foreach ([48, 51, 55, 56, 58, 62] as $pitch) {
            Storage::disk('public')->put(NoteSample::path('piano', self::NAMES[$pitch % 12], intdiv($pitch, 12) - 1), 'recording');
        }
        $this->assertNull(MinorModeVoicingSamples::resolve(MinorModeVoicingSamples::PREFIX.'/piano/c-natural-minor.wav'));
        $this->assertNull(MinorModeVoicingSamples::resolve(MinorModeVoicingSamples::PREFIX.'/tuba/c-natural-minor.wav'));
        $this->assertNull(MinorModeVoicingSamples::resolve(MinorModeVoicingSamples::PREFIX.'/piano/db-natural-minor.wav'));
        $this->assertNull(MinorModeVoicingSamples::resolve('other/piano/db-major-second.wav'));
    }

    public function test_six_variant_session_is_balanced_and_randomized(): void
    {
        $quiz = Quiz::factory()->create(['title' => MinorModeVoicingSamples::TITLE, 'category' => 'Modal Voicings', 'question_count' => 25]);
        foreach (MinorModeVoicingSamples::choices() as $index => $answer) {
            $quiz->questions()->create(['position' => $index + 1, 'prompt' => 'Listen', 'answer' => $answer, 'choices' => MinorModeVoicingSamples::choices()]);
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
        $this->assertSame(MinorModeVoicingSamples::TITLE.' #1', $titles[16]);
        $this->assertSame(MinorModeVoicingSamples::TITLE.' #2', $titles[17]);
        $this->assertSame($titles, QuizCatalog::all()['Modal Voicings']);
        $this->assertSame(MinorModeVoicingSamples::choices(), QuizAnswerPresets::presetFor('Modal Voicings', $titles[16]));
        $this->assertSame(MinorModeVoicingSamples::choices(), QuizAnswerPresets::presetFor('Modal Voicings', $titles[17]));
        $this->assertSame($titles, array_column(MobileCatalog::quizzes('Modal Voicings'), 'name'));
    }

    public function test_builder_creates_a_new_advanced_modal_quiz_without_reusing_the_scale_quiz(): void
    {
        $scale = Quiz::factory()->create(['title' => MinorModeVoicingSamples::TITLE, 'category' => 'Scales', 'slug' => 'minor-modes']);
        $scale->questions()->create(['position' => 1, 'prompt' => 'Keep scale', 'answer' => 'Keep scale', 'choices' => []]);
        $before = $scale->fresh()->load('questions')->toJson();
        ob_start();
        require base_path('scripts/audio/build_minor_mode_voicings.php');
        ob_end_clean();
        $quiz = Quiz::where('category', 'Modal Voicings')->where('title', MinorModeVoicingSamples::TITLE)->firstOrFail();
        $this->assertNotSame($scale->id, $quiz->id);
        $this->assertSame('modal-voicings-minor-modes', $quiz->slug);
        $this->assertSame('Advanced', $quiz->difficulty);
        $this->assertTrue($quiz->is_published);
        $this->assertSame(25, $quiz->question_count);
        $this->assertSame(6, $quiz->questions()->count());
        $this->assertSame($before, $scale->fresh()->load('questions')->toJson());
    }

    public function test_rebuild_only_replaces_the_minor_modes_lesson(): void
    {
        $quiz = Quiz::factory()->create(['title' => MinorModeVoicingSamples::LEGACY_TITLE, 'category' => 'Modal Voicings', 'slug' => 'modal-voicings-minor-modes']);
        $old = $quiz->questions()->create(['position' => 1, 'prompt' => 'Old', 'answer' => 'Old', 'choices' => []]);
        $other = Quiz::factory()->create(['title' => 'Lydian Mode', 'category' => 'Modal Voicings']);
        $other->questions()->create(['position' => 1, 'prompt' => 'Keep', 'answer' => 'Keep', 'choices' => []]);
        $before = $other->fresh()->load('questions')->toJson();
        $major = Quiz::factory()->create(['title' => 'Major Modes', 'category' => 'Modal Voicings']);
        $major->questions()->create(['position' => 1, 'prompt' => 'Keep Major', 'answer' => 'Keep', 'choices' => []]);
        $majorBefore = $major->fresh()->load('questions')->toJson();
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
        $scale = Quiz::factory()->create(['title' => MinorModeVoicingSamples::TITLE, 'category' => 'Scales']);
        $scale->questions()->create(['position' => 1, 'prompt' => 'Keep scale', 'answer' => 'Keep scale', 'choices' => []]);
        $scaleBefore = $scale->fresh()->load('questions')->toJson();
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('quiz.start', $quiz))->assertRedirect();
        $this->assertSame(1, $quiz->attempts()->count());
        ob_start();
        require base_path('scripts/audio/build_minor_mode_voicings.php');
        ob_end_clean();
        $this->assertSame(0, $quiz->attempts()->count());
        $this->assertSame(6, $quiz->questions()->count());
        $this->assertSame(MinorModeVoicingSamples::TITLE, $quiz->fresh()->title);
        $this->assertSame(25, $quiz->fresh()->question_count);
        $this->assertSame('modal-voicings-minor-modes', $quiz->fresh()->slug);
        $this->assertDatabaseMissing('quiz_questions', ['id' => $old->id]);
        $this->assertSame($before, $other->fresh()->load('questions')->toJson());
        $this->assertSame($majorBefore, $major->fresh()->load('questions')->toJson());
        $this->assertSame($ionianBefore, $ionian->fresh()->load('questions')->toJson());
        $this->assertSame($aeolianBefore, $aeolian->fresh()->load('questions')->toJson());
        $this->assertSame($dorianBefore, $dorian->fresh()->load('questions')->toJson());
        $this->assertSame($locrianBefore, $locrian->fresh()->load('questions')->toJson());
        $this->assertSame($phrygianBefore, $phrygian->fresh()->load('questions')->toJson());
        $this->assertSame($mixolydianBefore, $mixolydian->fresh()->load('questions')->toJson());
        $this->assertSame($scaleBefore, $scale->fresh()->load('questions')->toJson());
        foreach ($quiz->questions()->get() as $question) {
            $this->assertSame(MinorModeVoicingSamples::choices(), $question->choices);
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
