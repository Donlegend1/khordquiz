<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\User;
use App\Support\CadenceSamples;
use App\Support\NoteSample;
use App\Support\ProgressionRecognitionSamples;
use App\Support\QuizCatalog;
use App\Support\TwoFiveOneExtensionSamples;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TwoFiveOneExtensionLiveMixTest extends TestCase
{
    use RefreshDatabase;

    private const NAMES = ['Do', 'C#/Db', 'Re', 'D#/Eb', 'Mi', 'Fa', 'F#/Gb', 'So', 'G#/Ab', 'La', 'A#/Bb', 'Ti'];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Cache::flush();
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
        (new \ReflectionProperty(TwoFiveOneExtensionSamples::class, 'candidates'))->setValue(null, []);
        $full = (new \ReflectionClass(NoteSample::class))->getConstant('FULL_CHROMATIC_BANK_SLUGS');
        foreach (QuizCatalog::SOUND_SLUGS as $slug) {
            $register = CadenceSamples::preferredRegister($slug);
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

    public function test_sixty_curated_patterns_cover_every_quality_skeleton_and_all_twenty_seven_extensions(): void
    {
        $recipes = TwoFiveOneExtensionSamples::recipes();
        $chords = TwoFiveOneExtensionSamples::chords();
        $this->assertCount(60, $recipes);
        $this->assertCount(27, $chords);
        $this->assertEqualsCanonicalizing(array_keys(ProgressionRecognitionSamples::recipes()), array_column($recipes, 'skeleton'));
        $used = [];
        foreach ($recipes as $recipe) {
            $families = explode('-', $recipe['skeleton']);
            foreach ($recipe['chords'] as $index => $id) {
                $used[] = $id;
                $this->assertSame($families[$index], $chords[$id]['family']);
                $this->assertArrayHasKey($chords[$id]['family'], ProgressionRecognitionSamples::OPTIONS[[2, 5, 1][$index]]);
            }
        }
        $this->assertEqualsCanonicalizing(array_keys($chords), array_values(array_unique($used)));
        $this->assertSame([0, 3, 6, 10, 13, 17], $chords['min11b5b9']['intervals']);
    }

    public function test_all_keys_keep_exact_root_position_structures_and_correct_chord_timing(): void
    {
        $manifest = TwoFiveOneExtensionSamples::manifest();
        $this->assertCount(720, $manifest);
        $this->assertCount(720, array_unique(array_column($manifest, 'recipe')));
        foreach (CadenceSamples::KEYS as $key => $name) {
            $rows = array_values(array_filter($manifest, fn (array $row): bool => $row['key'] === $name));
            $this->assertCount(60, $rows);
            foreach ($rows as $row) {
                foreach ($row['samples'] as $slug => $compact) {
                    $this->assertSame(array_sum($row['sizes']), count($compact));
                    $notes = NoteSample::resolve(TwoFiveOneExtensionSamples::PREFIX.'/'.$slug.'/'.$row['recipe'].'.wav');
                    $offset = 0;
                    foreach ($row['chords'] as $index => $id) {
                        $size = $row['sizes'][$index];
                        $group = array_slice($compact, $offset, $size);
                        $pitches = array_map(fn (array $n): int => ($n[1] + 1) * 12 + $n[0], $group);
                        if ($slug === 'piano') {
                            $this->assertSame(($key + [2, 7, 0][$index]) % 12, $pitches[0] % 12);
                            $this->assertSame(TwoFiveOneExtensionSamples::chords()[$id]['intervals'], array_map(fn (int $p): int => $p - $pitches[0], $pitches));
                        }
                        foreach ($group as $voice => [$pc, $octave]) {
                            $this->assertSame(['slug' => $slug, 'degree' => self::NAMES[$pc], 'octave' => $octave, 'offset' => $index * 1.6], $notes[$offset + $voice]);
                        }
                        $offset += $size;
                    }
                }
            }
        }
        $this->assertNull(NoteSample::resolve(TwoFiveOneExtensionSamples::PREFIX.'/tuba/f-p01.wav'));
        $this->assertNull(NoteSample::resolve(TwoFiveOneExtensionSamples::PREFIX.'/piano/notakey-p01.wav'));
    }

    public function test_extensions_are_order_independent_without_changing_chord_order_or_quality(): void
    {
        $correct = '2 Minor 7(♭5)(9, 11) - 5 Dominant 7th(♭9, ♯11, 13) - 1 Major 7th(9, 13)';
        $reordered = '2 Minor 7(♭5) (11, 9) - 5 Dominant 7th(13, ♯11, ♭9) - 1 Major 7th(13, 9)';
        $this->assertTrue(TwoFiveOneExtensionSamples::answersMatch($reordered, $correct));
        $this->assertFalse(TwoFiveOneExtensionSamples::answersMatch(str_replace('(11, 9)', '(♭9, 11)', $reordered), $correct));
        $this->assertFalse(TwoFiveOneExtensionSamples::answersMatch(implode(' - ', array_reverse(explode(' - ', $reordered))), $correct));
        $this->assertFalse(TwoFiveOneExtensionSamples::answersMatch('2 Minor 7(♭5) - 5 Dominant 7th - 1 Major 7th', $correct));
        $quiz = Quiz::factory()->create(['title' => TwoFiveOneExtensionSamples::TITLE, 'category' => 'Chord Progressions', 'question_count' => 1]);
        $quiz->questions()->create(['position' => 1, 'prompt' => 'Choose extensions', 'answer' => $correct, 'choices' => []]);
        $this->actingAs(User::factory()->create())->post(route('quiz.start', $quiz))->assertRedirect();
        $this->post(route('quiz.answer', $quiz), ['choice' => $reordered])->assertRedirect();
        $this->assertSame(1, $quiz->attempts()->firstOrFail()->correct_answers);
    }

    public function test_rebuild_only_replaces_the_251_quiz_and_preserves_existing_recognition_content(): void
    {
        $quiz = Quiz::factory()->create(['title' => TwoFiveOneExtensionSamples::TITLE, 'slug' => '2-5-1-chord-degree']);
        $old = $quiz->questions()->create(['position' => 1, 'prompt' => 'Old', 'answer' => 'Old', 'choices' => []]);
        $other = Quiz::factory()->create(['title' => ProgressionRecognitionSamples::TITLE]);
        $other->questions()->create(['position' => 1, 'prompt' => 'Keep', 'answer' => 'Keep', 'choices' => []]);
        $before = $other->fresh()->load('questions')->toJson();
        ob_start();
        require base_path('scripts/audio/build_two_five_one_extensions.php');
        ob_end_clean();
        $this->assertSame(720, $quiz->questions()->count());
        $this->assertDatabaseMissing('quiz_questions', ['id' => $old->id]);
        $this->assertSame('2-5-1-chord-degree', $quiz->fresh()->slug);
        $this->assertSame($before, $other->fresh()->load('questions')->toJson());
    }
}
