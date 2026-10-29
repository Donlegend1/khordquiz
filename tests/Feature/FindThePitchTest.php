<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Support\NoteSample;
use App\Support\QuizCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FindThePitchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Cache::flush();
    }

    private function pool(): Quiz
    {
        require base_path('scripts/audio/seed_find_the_pitch.php');

        return Quiz::where('title', 'Find the Pitch')->firstOrFail();
    }

    private function recordOctave(string $slug, int $octave): void
    {
        foreach (array_keys(NoteSample::CHROMATIC_SLUGS) as $label) {
            $degree = NoteSample::equivalentDegree($label) ?? $label;
            $fileOctave = in_array($slug, ['tuba', 'voice'], true) && str_contains($label, '#') ? $octave - 1 : $octave;
            Storage::disk('public')->put(NoteSample::path($slug, $degree, $fileOctave), 'sample');
        }
        Cache::forget("note-sample-files:{$slug}");
    }

    public function test_pool_is_idempotent_and_catalog_places_it_after_find_the_note(): void
    {
        $quiz = $this->pool();
        $this->pool();

        $this->assertSame(84, $quiz->questions()->count());
        $this->assertSame(25, $quiz->question_count);
        $this->assertSame(1, Quiz::where('title', 'Find the Pitch')->count());
        $catalog = QuizCatalog::subcategories('Relative Pitch');
        $this->assertSame('Find the Pitch', $catalog[array_search('Find the Note', $catalog, true) + 1]);
    }

    public function test_explicit_pitch_uses_same_octave_solfege_fallback_and_never_guesses(): void
    {
        $this->recordOctave('piano', 4);

        $this->assertSame([['degree' => 'So', 'octave' => 4, 'offset' => 0.0]], NoteSample::fromScientificPitchAnswer('G4', 'piano'));
        $this->assertSame('C#/Db', NoteSample::fromScientificPitchAnswer('C#/Db4', 'piano')[0]['degree']);
        $this->assertNull(NoteSample::fromScientificPitchAnswer('G3', 'piano'));
        $this->assertNull(NoteSample::fromScientificPitchAnswer('G', 'piano'));
        $this->assertNull(NoteSample::fromScientificPitchAnswer('H4', 'piano'));
        $this->assertNull(NoteSample::fromScientificPitchAnswer('Do4', 'piano'));
        $this->assertSame(4, NoteSample::fromScientificPitchAnswer('G4')[0]['octave']);
    }

    public function test_legacy_sharps_use_the_known_filename_offset_without_transposing_the_pitch(): void
    {
        Storage::disk('public')->put('notes/tuba/csharp-c2.wav', 'C sharp 3');
        Storage::disk('public')->put('notes/tuba/csharp.wav', 'C sharp 4');

        $this->assertSame(2, NoteSample::fromScientificPitchAnswer('C#/Db3', 'tuba')[0]['octave']);
        $this->assertSame(3, NoteSample::fromScientificPitchAnswer('C#/Db4', 'tuba')[0]['octave']);
        $this->assertNull(NoteSample::fromScientificPitchAnswer('C#/Db5', 'tuba'));
    }

    public function test_narrow_instrument_is_available_and_draws_only_playable_pitches(): void
    {
        $quiz = $this->pool();
        $this->recordOctave('tuba', 3);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('quiz.category', ['relative-pitch']))->assertOk();
        $row = collect($response->viewData('page')['props']['quizzes'])->firstWhere('title', 'Find the Pitch');
        $this->assertContains('Tuba', $row['availableSounds']);

        $this->post(route('quiz.start', $quiz), ['sound' => 'Tuba'])->assertRedirect(route('quiz.play', $quiz));
        $attempt = QuizAttempt::where('quiz_id', $quiz->id)->firstOrFail();
        $this->assertSame('Tuba', $attempt->sound);
        $this->assertCount(25, $attempt->question_sequence);
        $answers = $quiz->questions()->whereIn('id', $attempt->question_sequence)->pluck('answer');
        $this->assertCount(12, $answers);
        $this->assertTrue($answers->every(fn ($answer) => str_ends_with($answer, '3')));

        $props = $this->get(route('quiz.play', $quiz))->assertOk()->viewData('page')['props'];
        $map = $props['question']['keyboard_audio'];
        $this->assertCount(12, $map);
        $this->assertArrayHasKey('G3', $map);
        $this->assertArrayNotHasKey('G4', $map);
        $current = QuizQuestion::findOrFail($attempt->questionIdAt(1));
        $this->assertSame($map[$current->answer], $props['question']['notes'][0]['url']);
    }

    public function test_piano_uses_octaves_two_through_six_and_grades_the_octave(): void
    {
        $quiz = $this->pool();
        foreach (NoteSample::SCIENTIFIC_PITCH_OCTAVES as $octave) {
            $this->recordOctave('piano', $octave);
        }
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('quiz.start', $quiz), ['sound' => 'Piano']);
        $attempt = QuizAttempt::where('quiz_id', $quiz->id)->firstOrFail();
        $this->assertCount(25, array_unique($attempt->question_sequence));
        $props = $this->get(route('quiz.play', $quiz))->assertOk()->viewData('page')['props'];
        $this->assertCount(60, $props['question']['keyboard_audio']);
        $this->assertArrayHasKey('C2', $props['question']['keyboard_audio']);
        $this->assertArrayHasKey('B6', $props['question']['keyboard_audio']);
        $this->assertArrayNotHasKey('G1', $props['question']['keyboard_audio']);
        $this->assertArrayNotHasKey('G7', $props['question']['keyboard_audio']);

        $question = QuizQuestion::findOrFail($attempt->questionIdAt(1));
        $wrongOctave = preg_replace_callback('/(\d+)$/', fn ($m) => $m[1] === '7' ? '6' : (string) ((int) $m[1] + 1), $question->answer);
        $this->post(route('quiz.answer', $quiz), ['choice' => $wrongOctave]);
        $this->assertSame(0, $attempt->fresh()->correct_answers);
        $this->assertDatabaseHas('quiz_attempt_answers', ['quiz_attempt_id' => $attempt->id, 'picked' => $wrongOctave, 'correct' => false]);
    }

    public function test_all_piano_family_instruments_exclude_outer_octaves_even_when_recorded(): void
    {
        $quiz = $this->pool();
        foreach (['Piano' => 'piano', 'EP' => 'ep', 'Upright Piano' => 'upright-piano', 'Organ' => 'organ'] as $sound => $slug) {
            foreach (NoteSample::SCIENTIFIC_PITCH_OCTAVES as $octave) {
                $this->recordOctave($slug, $octave);
            }
            $this->actingAs(User::factory()->create())->post(route('quiz.start', $quiz), ['sound' => $sound]);
            $props = $this->get(route('quiz.play', $quiz))->assertOk()->viewData('page')['props'];
            $map = $props['question']['keyboard_audio'];
            $this->assertCount(60, $map);
            $this->assertArrayHasKey('C2', $map);
            $this->assertArrayHasKey('B6', $map);
            $this->assertArrayNotHasKey('B1', $map);
            $this->assertArrayNotHasKey('C7', $map);
            $attempt = QuizAttempt::where('user_id', auth()->id())->where('quiz_id', $quiz->id)->firstOrFail();
            $answers = $quiz->questions()->whereIn('id', $attempt->question_sequence)->pluck('answer');
            $this->assertTrue($answers->every(fn ($answer) => in_array((int) substr($answer, -1), [2, 3, 4, 5, 6], true)));
        }

        $this->recordOctave('harpejji', 7);
        $this->assertNotNull(NoteSample::fromScientificPitchAnswer('G7', 'harpejji'));
    }

    public function test_harpejji_uses_c3_through_b7_without_restricting_other_guitars(): void
    {
        $quiz = $this->pool();
        foreach (NoteSample::SCIENTIFIC_PITCH_OCTAVES as $octave) {
            $this->recordOctave('harpejji', $octave);
        }
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('quiz.start', $quiz), ['sound' => 'Harpejji']);
        $props = $this->get(route('quiz.play', $quiz))->assertOk()->viewData('page')['props'];
        $map = $props['question']['keyboard_audio'];
        $this->assertCount(60, $map);
        $this->assertArrayHasKey('C3', $map);
        $this->assertArrayHasKey('B7', $map);
        $this->assertArrayNotHasKey('B2', $map);
        $attempt = QuizAttempt::where('quiz_id', $quiz->id)->firstOrFail();
        $answers = $quiz->questions()->whereIn('id', $attempt->question_sequence)->pluck('answer');
        $this->assertTrue($answers->every(fn ($answer) => in_array((int) substr($answer, -1), [3, 4, 5, 6, 7], true)));

        foreach (['guitar', 'electric-guitar'] as $slug) {
            $this->recordOctave($slug, 2);
            $this->assertNotNull(NoteSample::fromScientificPitchAnswer('C2', $slug));
        }
    }

    public function test_solo_choir_uses_g2_through_g5_inclusively(): void
    {
        $quiz = $this->pool();
        foreach (NoteSample::SCIENTIFIC_PITCH_OCTAVES as $octave) {
            $this->recordOctave('voice', $octave);
        }
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('quiz.start', $quiz), ['sound' => 'Voice']);
        $props = $this->get(route('quiz.play', $quiz))->assertOk()->viewData('page')['props'];
        $map = $props['question']['keyboard_audio'];
        $this->assertCount(37, $map);
        $this->assertArrayHasKey('G2', $map);
        $this->assertArrayHasKey('G5', $map);
        $this->assertArrayNotHasKey('F#/Gb2', $map);
        $this->assertArrayNotHasKey('G#/Ab5', $map);
        $attempt = QuizAttempt::where('quiz_id', $quiz->id)->firstOrFail();
        $answers = $quiz->questions()->whereIn('id', $attempt->question_sequence)->pluck('answer');
        $this->assertTrue($answers->every(fn ($answer) => array_key_exists($answer, $map)));
        $this->assertNull(NoteSample::fromScientificPitchAnswer('F2', 'voice'));
        $this->assertNull(NoteSample::fromScientificPitchAnswer('A5', 'voice'));
    }

    public function test_ensemble_choir_uses_e2_through_d6_inclusively(): void
    {
        $quiz = $this->pool();
        foreach (NoteSample::SCIENTIFIC_PITCH_OCTAVES as $octave) {
            $this->recordOctave('ensemble-choir', $octave);
        }
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('quiz.start', $quiz), ['sound' => 'Ensemble Choir']);
        $props = $this->get(route('quiz.play', $quiz))->assertOk()->viewData('page')['props'];
        $map = $props['question']['keyboard_audio'];
        $this->assertCount(47, $map);
        $this->assertArrayHasKey('E2', $map);
        $this->assertArrayHasKey('D6', $map);
        $this->assertArrayNotHasKey('D#/Eb2', $map);
        $this->assertArrayNotHasKey('D#/Eb6', $map);
        $attempt = QuizAttempt::where('quiz_id', $quiz->id)->firstOrFail();
        $answers = $quiz->questions()->whereIn('id', $attempt->question_sequence)->pluck('answer');
        $this->assertTrue($answers->every(fn ($answer) => array_key_exists($answer, $map)));
    }

    public function test_switching_to_narrow_instrument_preserves_history_and_redraws_remaining_slots(): void
    {
        $quiz = $this->pool();
        $this->recordOctave('piano', 2);
        $this->recordOctave('tuba', 3);
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('quiz.start', $quiz), ['sound' => 'Piano']);
        $attempt = QuizAttempt::where('quiz_id', $quiz->id)->firstOrFail();
        $firstId = $attempt->questionIdAt(1);
        $this->post(route('quiz.answer', $quiz), ['choice' => QuizQuestion::findOrFail($firstId)->answer]);
        $this->post(route('quiz.next', $quiz));
        $this->post(route('quiz.start', $quiz), ['sound' => 'Tuba']);

        $attempt->refresh();
        $this->assertSame($firstId, $attempt->question_sequence[0]);
        $this->assertSame(1, $attempt->correct_answers);
        $this->assertSame(2, $attempt->stopped_at_question);
        $remaining = $quiz->questions()->whereIn('id', array_slice($attempt->question_sequence, 1))->pluck('answer');
        $this->assertTrue($remaining->every(fn ($answer) => str_ends_with($answer, '3')));
    }

    public function test_resuming_an_old_piano_attempt_replaces_now_excluded_pitches(): void
    {
        $quiz = $this->pool();
        $this->recordOctave('piano', 2);
        $this->recordOctave('piano', 7);
        $user = User::factory()->create();
        $excluded = $quiz->questions()->where('answer', 'G7')->firstOrFail();
        $attempt = QuizAttempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'status' => QuizAttempt::STATUS_IN_PROGRESS,
            'sound' => 'Piano',
            'stopped_at_question' => 1,
            'question_sequence' => array_fill(0, 25, $excluded->id),
        ]);

        $this->actingAs($user)->post(route('quiz.start', $quiz), ['sound' => 'Piano']);
        $attempt->refresh();
        $this->assertCount(25, $attempt->question_sequence);
        $this->assertNotContains($excluded->id, $attempt->question_sequence);
        $props = $this->get(route('quiz.play', $quiz))->assertOk()->viewData('page')['props'];
        $this->assertNotEmpty($props['question']['notes']);
    }

    public function test_admin_live_preview_resolves_explicit_pitch(): void
    {
        $quiz = $this->pool();
        $this->recordOctave('tuba', 3);
        $admin = User::factory()->admin()->create();
        $props = $this->actingAs($admin)->get(route('admin.audio-quiz.lesson', ['relative-pitch', 'find-the-pitch']))->assertOk()->viewData('page')['props'];
        $questions = collect($props['quiz']['questions']);
        $g3 = $questions->firstWhere('answer', 'G3');
        $g4 = $questions->firstWhere('answer', 'G4');
        $previews = collect($g3['missing_instrument_previews']);
        $this->assertNotNull($previews->firstWhere('sound', 'Tuba'));
        $this->assertNull(collect($g4['missing_instrument_previews'])->firstWhere('sound', 'Tuba'));
    }

    public function test_find_the_note_still_uses_its_existing_register_resolution(): void
    {
        $this->recordOctave('piano', 3);
        $this->recordOctave('piano', 4);
        $this->assertSame(4, NoteSample::fromAnswer('G', 'melodic', 'piano')[0]['octave']);
        $this->assertSame(3, NoteSample::fromScientificPitchAnswer('G3', 'piano')[0]['octave']);
    }
}
