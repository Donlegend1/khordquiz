<?php

namespace Tests\Feature;

use App\Http\Controllers\QuizController;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Support\NoteSample;
use App\Support\QuizCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FindTheNoteKeyboardAudioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Cache::flush();
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
    }

    protected function tearDown(): void
    {
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
        parent::tearDown();
    }

    public function test_keyboard_uses_each_instruments_live_question_register_without_attached_audio(): void
    {
        $quiz = Quiz::factory()->create(['category' => 'Relative Pitch', 'title' => 'Find the Note']);
        $choices = array_keys(NoteSample::CHROMATIC_SLUGS);
        foreach ($choices as $index => $answer) {
            $quiz->questions()->create(['position' => $index + 1, 'answer' => $answer, 'choices' => $choices, 'prompt' => 'Identify the note.']);
        }
        foreach (QuizCatalog::SOUND_SLUGS as $slug) {
            foreach ($choices as $answer) {
                foreach (range(1, 5) as $octave) {
                    Storage::disk('public')->put(NoteSample::path($slug, NoteSample::equivalentDegree($answer) ?? $answer, $octave), 'sample');
                }
            }
        }
        $controller = app(QuizController::class);
        $keyboard = new \ReflectionMethod($controller, 'keyboardAudioMap');
        $keyKeyboard = new \ReflectionMethod($controller, 'defaultInstrumentKeyboardAudio');
        foreach (QuizCatalog::SOUND_SLUGS as $sound => $slug) {
            $map = $keyboard->invoke($controller, $quiz, $sound);
            $this->assertCount(12, $map, $sound);
            $request = Request::create('/');
            $request->setUserResolver(fn () => new User(['default_instrument' => $sound]));
            $this->assertSame($map, $keyKeyboard->invoke($controller, $request), $sound);
            $this->assertSame(['F#/Gb' => $map['F#/Gb']], $keyKeyboard->invoke($controller, $request, 'F#/Gb'), $sound);
            foreach ($choices as $answer) {
                $note = NoteSample::fromAnswer($answer, 'melodic', $slug)[0];
                $this->assertSame(NoteSample::url(NoteSample::path($slug, $note['degree'], $note['octave'])), $map[$answer], $sound.'/'.$answer);
            }
        }
        $piano = $keyboard->invoke($controller, $quiz, 'Piano');
        $this->assertSame(NoteSample::url('notes/piano/do-c4.wav'), $piano['C']);
        $this->assertSame(NoteSample::url('notes/piano/fsharp-c4.wav'), $piano['F#/Gb']);

        $user = User::factory()->create();
        $this->actingAs($user)->post(route('quiz.start', $quiz), ['sound' => 'Piano'])->assertRedirect();
        $attempt = QuizAttempt::where('quiz_id', $quiz->id)->firstOrFail();
        $answer = $quiz->questions()->findOrFail($attempt->questionIdAt(1))->answer;
        $props = $this->get(route('quiz.play', $quiz))->assertOk()->viewData('page')['props'];
        $this->assertSame($piano[$answer], $props['question']['notes'][0]['url']);
        $this->assertSame($piano, $props['question']['keyboard_audio']);
    }

    public function test_key_keyboard_uses_the_same_register_without_a_find_the_note_lesson(): void
    {
        Storage::disk('public')->put('notes/piano/do-c4.wav', 'sample');
        $request = Request::create('/');
        $request->setUserResolver(fn () => new User(['default_instrument' => 'Piano']));
        $keyboard = new \ReflectionMethod(QuizController::class, 'defaultInstrumentKeyboardAudio');
        $controller = app(QuizController::class);

        $this->assertSame(['C' => NoteSample::url('notes/piano/do-c4.wav')], $keyboard->invoke($controller, $request));
        $this->assertSame([], $keyboard->invoke($controller, $request, 'F#/Gb'));
    }

    public function test_attached_note_and_keyboard_stay_in_the_same_register(): void
    {
        $quiz = Quiz::factory()->create(['category' => 'Relative Pitch', 'title' => 'Find the Note']);
        $question = $quiz->questions()->create(['position' => 1, 'answer' => 'C', 'choices' => ['C'], 'prompt' => 'Identify the note.']);
        $question->audioVariants()->create(['sound' => 'Piano', 'audio_path' => 'notes/piano/c-c4.wav']);
        Storage::disk('public')->put('notes/piano/c-c4.wav', 'sample');
        $map = (new \ReflectionMethod(QuizController::class, 'keyboardAudioMap'))->invoke(app(QuizController::class), $quiz, 'Piano');
        $this->assertSame(['C' => NoteSample::url('notes/piano/c-c4.wav')], $map);
        $request = Request::create('/');
        $request->setUserResolver(fn () => new User(['default_instrument' => 'Piano']));
        $keyMap = (new \ReflectionMethod(QuizController::class, 'defaultInstrumentKeyboardAudio'))->invoke(app(QuizController::class), $request, 'C');
        $this->assertSame($map, $keyMap);
    }
}
