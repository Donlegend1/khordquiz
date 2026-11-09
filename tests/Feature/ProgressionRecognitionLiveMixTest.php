<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Support\CadenceSamples;
use App\Support\NoteSample;
use App\Support\ProgressionRecognitionSamples;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProgressionRecognitionLiveMixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Cache::flush();
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
        foreach (range(48, 76) as $pitch) {
            $note = ['Do', 'C#/Db', 'Re', 'D#/Eb', 'Mi', 'Fa', 'F#/Gb', 'So', 'G#/Ab', 'La', 'A#/Bb', 'Ti'][$pitch % 12];
            Storage::disk('public')->put(NoteSample::path('piano', $note, intdiv($pitch, 12) - 1), 'recording');
        }
    }

    public function test_all_sixty_combinations_use_the_approved_qualities_and_fixed_inversions_in_f(): void
    {
        $recipes = ProgressionRecognitionSamples::recipes();
        $this->assertCount(60, $recipes);
        $this->assertCount(60, array_unique(array_column($recipes, 'answer')));
        foreach ($recipes as $id => $recipe) {
            [$two, $five, $one] = explode('-', $id);
            $this->assertArrayHasKey($two, ProgressionRecognitionSamples::OPTIONS[2]);
            $this->assertArrayHasKey($five, ProgressionRecognitionSamples::OPTIONS[5]);
            $this->assertArrayHasKey($one, ProgressionRecognitionSamples::OPTIONS[1]);
            $chords = $recipe['chords'];
            foreach ($chords as $chord) {
                $this->assertCount(4, $chord);
                $this->assertCount(4, array_unique(array_map(fn (int $pitch): int => $pitch % 12, $chord)));
            }
            $this->assertSame(7, $chords[0][0] % 12);
            $this->assertSame($five === 'm7b5' ? 6 : 7, $chords[1][0] % 12);
            $this->assertSame(5, $chords[2][0] % 12);
            $this->assertSame(0, $chords[1][2] % 12);
            $notes = NoteSample::resolve(ProgressionRecognitionSamples::PREFIX.'/piano/'.$id.'.wav');
            $this->assertCount(12, $notes);
            $this->assertSame([0.0, 1.6, 3.2], array_values(array_unique(array_column($notes, 'offset'))));
        }
        $this->assertSame([[55, 58, 62, 65], [55, 58, 60, 64], [53, 57, 60, 64]], $recipes['min7-dom7-maj7']['chords']);
        $this->assertSame([54, 58, 60, 63], $recipes['min7-m7b5-maj7']['chords'][1]);
        $this->assertNull(NoteSample::resolve(ProgressionRecognitionSamples::PREFIX.'/piano/invalid.wav'));
    }

    public function test_builder_replaces_only_the_existing_recognition_quiz_and_preserves_its_url(): void
    {
        $quiz = Quiz::factory()->create(['title' => 'Progression recognition', 'slug' => 'progression-recognition']);
        $old = $quiz->questions()->create(['position' => 1, 'prompt' => 'Old', 'answer' => 'Old', 'choices' => []]);
        $other = Quiz::factory()->create(['title' => '2-5-1 chord degree']);
        ob_start();
        require base_path('scripts/audio/build_progression_recognition.php');
        ob_end_clean();
        $this->assertSame(ProgressionRecognitionSamples::TITLE, $quiz->fresh()->title);
        $this->assertSame('progression-recognition', $quiz->fresh()->slug);
        $this->assertSame(60, $quiz->questions()->count());
        $this->assertDatabaseMissing('quiz_questions', ['id' => $old->id]);
        $this->assertSame('2-5-1 chord degree', $other->fresh()->title);
        foreach ($quiz->questions()->with('audioVariants')->get() as $question) {
            $this->assertCount(1, $question->audioVariants);
            $this->assertStringStartsWith(ProgressionRecognitionSamples::PREFIX.'/piano/', $question->audioVariants->first()->audio_path);
        }
    }

    public function test_part_two_has_all_sixty_combinations_in_every_key_with_the_same_inversions(): void
    {
        $manifest = ProgressionRecognitionSamples::manifest(2);
        $this->assertCount(720, $manifest);
        $compact = ProgressionRecognitionSamples::mobileManifest($manifest);
        $this->assertCount(720, $compact);
        foreach ($compact[0]['samples']['piano'] as $index => [$degree, $octave]) {
            $names = ['Do', 'C#/Db', 'Re', 'D#/Eb', 'Mi', 'Fa', 'F#/Gb', 'So', 'G#/Ab', 'La', 'A#/Bb', 'Ti'];
            $this->assertSame(['degree' => $names[$degree], 'octave' => $octave, 'offset' => intdiv($index, 4) * 1.6], $manifest[0]['samples']['piano'][$index]);
        }
        $this->assertCount(720, array_unique(array_column($manifest, 'recipe')));
        $degrees = ['Do', 'C#/Db', 'Re', 'D#/Eb', 'Mi', 'Fa', 'F#/Gb', 'So', 'G#/Ab', 'La', 'A#/Bb', 'Ti'];
        foreach (CadenceSamples::KEYS as $key => $name) {
            $rows = array_values(array_filter($manifest, fn (array $row): bool => $row['key'] === $name));
            $this->assertCount(60, $rows);
            foreach ($rows as $row) {
                $notes = $row['samples']['piano'];
                $this->assertCount(12, $notes);
                $pitches = array_map(fn (array $n): int => ($n['octave'] + 1) * 12 + array_search($n['degree'], $degrees, true), $notes);
                $this->assertSame(($key + 2) % 12, $pitches[0] % 12);
                $this->assertSame($key, $pitches[8] % 12);
                $five = explode(' - ', $row['answer'])[1];
                $this->assertSame(($key + (str_contains($five, '♭5') ? 1 : 2)) % 12, $pitches[4] % 12);
                $id = substr($row['recipe'], strlen($name) + 1);
                $base = array_merge(...ProgressionRecognitionSamples::recipes()[$id]['chords']);
                $delta = $pitches[0] - $base[0];
                foreach ($pitches as $index => $pitch) {
                    $this->assertGreaterThanOrEqual(48, $pitch);
                    $this->assertLessThanOrEqual(76, $pitch);
                    $this->assertSame($delta, $pitch - $base[$index]);
                }
                if ($name === 'f') {
                    $this->assertSame(NoteSample::resolve(ProgressionRecognitionSamples::PREFIX.'/piano/'.$id.'.wav'), NoteSample::resolve(ProgressionRecognitionSamples::PART_TWO_PREFIX.'/piano/'.$row['recipe'].'.wav'));
                }
            }
        }
    }

    public function test_part_two_builder_creates_a_separate_quiz_without_changing_part_one(): void
    {
        $partOne = Quiz::factory()->create(['title' => ProgressionRecognitionSamples::TITLE]);
        $partOne->questions()->create(['position' => 1, 'prompt' => 'Keep', 'answer' => 'Keep', 'choices' => []]);
        $before = $partOne->fresh()->load('questions')->toJson();
        $recognitionPart = 2;
        ob_start();
        require base_path('scripts/audio/build_progression_recognition.php');
        ob_end_clean();
        $partTwo = Quiz::where('title', ProgressionRecognitionSamples::PART_TWO_TITLE)->firstOrFail();
        $this->assertSame(720, $partTwo->questions()->count());
        $this->assertSame($before, $partOne->fresh()->load('questions')->toJson());
        $this->assertNotSame($partOne->id, $partTwo->id);
    }
}
