<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\User;
use App\Support\NoteSample;
use App\Support\QuizCatalog;
use App\Support\ReferenceAudioTracks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuizReferenceInstrumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Cache::flush();
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
    }

    /** Builds the same per-sound result present() used to return, via the
     * lazy pool()/resolveOne() interface member playback actually uses now. */
    private function resolveAllSounds(array $tracks): array
    {
        $presenter = new ReferenceAudioTracks;
        $groups = $presenter->pool($tracks);
        if ($groups === []) {
            return [];
        }
        $groupId = $groups[0]['id'];
        $result = [];
        foreach (QuizCatalog::SOUND_SLUGS as $sound => $slug) {
            $audio = $presenter->resolveOne($tracks, $groupId, $sound);
            if ($audio !== null) {
                $result[] = ['sound' => $sound, ...$audio];
            }
        }

        return $result;
    }

    private function recordBanks(): void
    {
        $full = (new \ReflectionClass(NoteSample::class))->getConstant('FULL_CHROMATIC_BANK_SLUGS');
        foreach (QuizCatalog::SOUND_SLUGS as $slug) {
            $canonical = in_array($slug, ['celesta', 'vibraphone', 'saxophone', 'voice'], true);
            if ($canonical) {
                Storage::disk('public')->put('notes/'.$slug.'/gm-bank.json', '{}');
            }
            foreach (range(24, 107) as $midi) {
                $label = array_keys(NoteSample::CHROMATIC_SLUGS)[$midi % 12];
                $degree = NoteSample::equivalentDegree($label) ?? $label;
                $octave = intdiv($midi, 12) - 1;
                if (! $canonical) {
                    $octave += ['organ' => 1, 'harpejji' => 1, 'upright-bass' => 2][$slug] ?? 0;
                    if (str_contains($label, '#') && ! in_array($slug, $full, true)) {
                        $octave--;
                    }
                }
                Storage::disk('public')->put(NoteSample::path($slug, $degree, $octave), 'note');
            }
        }
    }

    public function test_scale_references_use_every_exact_instrument_including_upright_piano_and_harpejji(): void
    {
        $this->recordBanks();
        $tracks = $this->resolveAllSounds([
            ['id' => 'old-piano', 'path' => 'reference-scales/piano.wav', 'label' => 'C Major Scale', 'sound' => 'Piano'],
            ['id' => 'old-guitar', 'path' => 'reference-scales/guitar.wav', 'label' => 'C Major Scale', 'sound' => 'Guitar'],
        ]);
        $this->assertCount(count(QuizCatalog::SOUND_SLUGS), $tracks);
        foreach ($tracks as $track) {
            $slug = QuizCatalog::SOUND_SLUGS[$track['sound']];
            $this->assertNull($track['url']);
            $this->assertCount(15, $track['notes']);
            foreach ($track['notes'] as $note) {
                $this->assertStringContainsString('/notes/'.$slug.'/', $note['url']);
            }
            $this->assertSame(0.0, $track['notes'][0]['offset']);
            $this->assertSame(7.700000000000001, $track['notes'][14]['offset']);
            $this->assertSame($track['notes'][0]['url'], $track['notes'][14]['url']);
        }
    }

    public function test_category_exposes_a_cheap_reference_pool_with_no_audio_resolved_up_front(): void
    {
        $this->recordBanks();
        $quiz = Quiz::factory()->create([
            'category' => 'Relative Pitch', 'title' => 'Relative Tone Pitch #1', 'is_published' => true,
            'reference_audio_tracks' => [['id' => 'piano', 'path' => 'reference-scales/piano.wav', 'label' => 'C Major Scale', 'sound' => 'Piano']],
        ]);
        $quiz->questions()->create(['position' => 1, 'prompt' => 'Listen.', 'answer' => 'Do', 'choices' => ['Do', 'Re']]);
        $this->actingAs(User::factory()->create(['default_instrument' => 'Upright Piano']));
        $props = $this->get(route('quiz.category', ['relative-pitch']))->assertOk()->viewData('page')['props'];
        $row = collect($props['quizzes'])->firstWhere('slug', $quiz->slug);

        // Just the one distinct-label group, with no per-instrument audio
        // resolved — the category page never calls NoteSample::resolve() at all.
        $this->assertCount(1, $row['referenceAudioPool']);
        $group = $row['referenceAudioPool'][0];
        $this->assertSame('C Major Scale', $group['label']);
        $this->assertArrayNotHasKey('notes', $group);
        $this->assertArrayNotHasKey('url', $group);
    }

    public function test_reference_audio_clip_route_resolves_one_clip_lazily_for_the_requested_instrument(): void
    {
        $this->recordBanks();
        $quiz = Quiz::factory()->create([
            'category' => 'Relative Pitch', 'title' => 'Relative Tone Pitch #1', 'is_published' => true,
            'reference_audio_tracks' => [['id' => 'piano', 'path' => 'reference-scales/piano.wav', 'label' => 'C Major Scale', 'sound' => 'Piano']],
        ]);
        $this->actingAs(User::factory()->create());

        $groupId = (new ReferenceAudioTracks)->pool($quiz->reference_audio_tracks)[0]['id'];

        foreach (['Upright Piano', 'Harpejji', 'Guitar', 'Piano', 'Flute'] as $sound) {
            $clip = $this->getJson(route('quiz.reference-audio-clip', [$quiz, $groupId]).'?sound='.urlencode($sound))
                ->assertOk()
                ->json('clip');
            $this->assertNotNull($clip);
            $this->assertStringContainsString('/notes/'.QuizCatalog::SOUND_SLUGS[$sound].'/', $clip['notes'][0]['url']);
        }

        // An unknown group id, or an instrument with no real audio, resolves to null rather than erroring.
        $this->assertNull($this->getJson(route('quiz.reference-audio-clip', [$quiz, 'not-a-real-group']).'?sound=Piano')->assertOk()->json('clip'));
        $this->assertNull($this->getJson(route('quiz.reference-audio-clip', [$quiz, $groupId]))->assertOk()->json('clip'));
    }

    public function test_chord_and_interval_references_are_rebuilt_in_the_requested_instrument(): void
    {
        $this->recordBanks();
        foreach ([
            ['path' => 'basic-triads/piano/major.wav', 'label' => 'Major'],
            ['path' => 'reference-intervals/melodic-diatonic/piano/major-2nd.wav', 'label' => 'Major 2nd'],
            ['path' => 'extensions/chord-naming/piano/dominant-bare-none-0.wav', 'label' => 'Dominant'],
        ] as $template) {
            $tracks = $this->resolveAllSounds([['id' => 'template', 'sound' => 'Piano', ...$template]]);
            $track = collect($tracks)->firstWhere('sound', 'Harpejji');
            $this->assertNotNull($track);
            foreach ($track['notes'] as $note) {
                $this->assertStringContainsString('/notes/harpejji/', $note['url']);
            }
        }
    }

    public function test_missing_samples_never_relabel_a_different_instrument_recording(): void
    {
        Storage::disk('public')->put('uploads/custom.wav', 'original');
        $tracks = $this->resolveAllSounds([['id' => 'custom', 'path' => 'uploads/custom.wav', 'label' => 'Custom reference', 'sound' => 'Guitar']]);
        $this->assertSame(['Guitar'], array_column($tracks, 'sound'));
        $this->assertStringContainsString('/uploads/custom.wav', $tracks[0]['url']);
        $this->assertSame([], $this->resolveAllSounds([]));

        Storage::disk('public')->put('reference-scales/guitar.wav', 'guitar');
        $this->assertSame([], $this->resolveAllSounds([['path' => 'reference-scales/guitar.wav', 'label' => 'Scale', 'sound' => 'Harpejji']]));
    }
}
