<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminQuizTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_member_quiz_and_streak_stats(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(3)->create([
            'current_streak' => 4,
            'last_practiced_on' => now()->toDateString(),
        ]);
        Quiz::factory()->count(2)->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->where('auth.user.role', 'admin')
                ->where('stats.members', 3)
                ->where('stats.quizzes', 2)
                ->where('stats.publishedQuizzes', 2)
                ->where('stats.activeStreaks', 3)
                ->has('recentStreaks', 3)
                ->has('quizzes', 2));
    }

    public function test_members_cannot_manage_quizzes(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)
            ->get(route('admin.quizzes.create'))
            ->assertForbidden();

        $this->actingAs($member)
            ->post(route('admin.quizzes.store'), [
                'title' => 'Find the Note',
                'category' => 'Relative Pitch',
                'difficulty' => 'Beginner',
                'description' => 'Name the note you hear.',
                'question_count' => 25,
                'is_published' => true,
            ])
            ->assertForbidden();
    }

    public function test_admin_can_add_a_quiz(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.quizzes.store'), [
                'title' => 'Find the Note',
                'category' => 'Relative Pitch',
                'difficulty' => 'Beginner',
                'description' => 'Name the note you hear.',
                'is_published' => true,
                'questions' => $this->sampleQuestions(),
            ])
            ->assertRedirect(route('admin.quizzes.index'))
            ->assertSessionHas('success', 'Quiz added.');

        $this->actingAs($admin)
            ->get(route('admin.quizzes.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('flash.success', 'Quiz added.'));

        $this->assertDatabaseHas('quizzes', [
            'title' => 'Find the Note',
            'slug' => 'find-the-note',
            'question_count' => 2,
            'created_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('quiz_questions', [
            'prompt' => 'Which note is a perfect 5th above C?',
            'answer' => 'G',
        ]);
    }

    public function test_a_second_quiz_with_the_same_title_gets_its_own_slug(): void
    {
        $admin = User::factory()->admin()->create();
        Quiz::factory()->create([
            'title' => 'Find the Note',
            'slug' => 'find-the-note',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.quizzes.store'), [
                'title' => 'Find the Note',
                'category' => 'Relative Pitch',
                'difficulty' => 'Beginner',
                'is_published' => true,
                'questions' => $this->sampleQuestions(),
            ])
            ->assertRedirect(route('admin.quizzes.index'));

        $this->assertDatabaseHas('quizzes', ['slug' => 'find-the-note-2']);
    }

    public function test_quiz_title_is_required(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.quizzes.create'))
            ->post(route('admin.quizzes.store'), [
                'title' => '',
                'category' => 'Intervals',
                'difficulty' => 'Beginner',
                'question_count' => 25,
                'is_published' => true,
            ])
            ->assertRedirect(route('admin.quizzes.create'))
            ->assertSessionHasErrors('title');
    }

    public function test_admin_can_update_and_delete_a_quiz(): void
    {
        $admin = User::factory()->admin()->create();
        $quiz = Quiz::factory()->create(['title' => 'Cadences']);
        QuizQuestion::factory()->create([
            'quiz_id' => $quiz->id,
            'prompt' => 'Old cadence question',
            'answer' => 'Authentic',
        ]);
        $attempt = QuizAttempt::factory()->create([
            'quiz_id' => $quiz->id,
            'user_id' => User::factory()->create()->id,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.quizzes.update', $quiz), [
                'title' => 'Cadences Revised',
                'category' => 'Chord Progressions',
                'difficulty' => 'Intermediate',
                'description' => 'Hear the cadence.',
                'is_published' => false,
                'questions' => [
                    [
                        'prompt' => 'In C major, G to C is which cadence?',
                        'choices' => ['Authentic', 'Plagal', 'Half', 'Deceptive'],
                        'correct' => 0,
                    ],
                ],
            ])
            ->assertRedirect(route('admin.quizzes.index'));

        $this->assertDatabaseHas('quizzes', [
            'id' => $quiz->id,
            'title' => 'Cadences Revised',
            'question_count' => 1,
            'is_published' => false,
        ]);
        $this->assertDatabaseMissing('quiz_questions', ['prompt' => 'Old cadence question']);
        $this->assertDatabaseHas('quiz_questions', [
            'quiz_id' => $quiz->id,
            'prompt' => 'In C major, G to C is which cadence?',
            'answer' => 'Authentic',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.quizzes.destroy', $quiz))
            ->assertRedirect();

        $this->assertDatabaseMissing('quizzes', ['id' => $quiz->id]);
        $this->assertDatabaseMissing('quiz_attempts', ['id' => $attempt->id]);
        $this->assertDatabaseMissing('quiz_questions', ['quiz_id' => $quiz->id]);
    }

    public function test_the_marked_answer_has_to_be_one_of_the_choices(): void
    {
        $admin = User::factory()->admin()->create();
        $questions = $this->sampleQuestions();
        $questions[0]['correct'] = 9;

        $this->actingAs($admin)
            ->from(route('admin.quizzes.create'))
            ->post(route('admin.quizzes.store'), [
                'title' => 'Intervals',
                'category' => 'Intervals',
                'difficulty' => 'Beginner',
                'is_published' => true,
                'questions' => $questions,
            ])
            ->assertRedirect(route('admin.quizzes.create'))
            ->assertSessionHasErrors('questions.0.correct');
    }

    public function test_a_question_can_include_optional_audio_and_video(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $questions = $this->sampleQuestions();
        $questions[0]['audio'] = UploadedFile::fake()->create('note.mp3', 120, 'audio/mpeg');
        $questions[0]['video'] = UploadedFile::fake()->create('note.mp4', 240, 'video/mp4');

        $this->actingAs($admin)
            ->post(route('admin.quizzes.store'), [
                'title' => 'Find the Note',
                'category' => 'Relative Pitch',
                'difficulty' => 'Beginner',
                'is_published' => true,
                'questions' => $questions,
            ])
            ->assertRedirect(route('admin.quizzes.index'));

        $question = QuizQuestion::query()->where('prompt', 'Which note is a perfect 5th above C?')->first();

        $this->assertNotNull($question?->audio_path);
        $this->assertNotNull($question?->video_path);
        Storage::disk('public')->assertExists($question->audio_path);
        Storage::disk('public')->assertExists($question->video_path);

        $this->assertNull(
            QuizQuestion::query()->where('prompt', 'Which note is a major 3rd above A?')->value('audio_path'),
        );
    }

    public function test_editing_a_question_keeps_its_audio_until_it_is_removed(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('questions/audio/kept.mp3', 'audio');

        $admin = User::factory()->admin()->create();
        $quiz = Quiz::factory()->create();
        $question = QuizQuestion::factory()->create([
            'quiz_id' => $quiz->id,
            'prompt' => 'Name the interval.',
            'audio_path' => 'questions/audio/kept.mp3',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.quizzes.update', $quiz), [
                'title' => $quiz->title,
                'category' => 'Intervals',
                'difficulty' => 'Beginner',
                'is_published' => true,
                'questions' => [
                    [
                        'id' => $question->id,
                        'prompt' => 'Name the interval.',
                        'choices' => ['Major 3rd', 'Minor 3rd'],
                        'correct' => 0,
                        'keep_audio' => true,
                        'keep_video' => false,
                    ],
                ],
            ])
            ->assertRedirect(route('admin.quizzes.index'));

        $this->assertDatabaseHas('quiz_questions', [
            'quiz_id' => $quiz->id,
            'audio_path' => 'questions/audio/kept.mp3',
        ]);
        Storage::disk('public')->assertExists('questions/audio/kept.mp3');

        $kept = QuizQuestion::query()->where('quiz_id', $quiz->id)->first();

        $this->actingAs($admin)
            ->put(route('admin.quizzes.update', $quiz), [
                'title' => $quiz->title,
                'category' => 'Intervals',
                'difficulty' => 'Beginner',
                'is_published' => true,
                'questions' => [
                    [
                        'id' => $kept->id,
                        'prompt' => 'Name the interval.',
                        'choices' => ['Major 3rd', 'Minor 3rd'],
                        'correct' => 0,
                        'keep_audio' => false,
                        'keep_video' => false,
                    ],
                ],
            ])
            ->assertRedirect(route('admin.quizzes.index'));

        $this->assertDatabaseHas('quiz_questions', [
            'quiz_id' => $quiz->id,
            'audio_path' => null,
        ]);
        Storage::disk('public')->assertMissing('questions/audio/kept.mp3');
    }

    public function test_a_question_rejects_a_file_that_is_not_audio(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $questions = $this->sampleQuestions();
        $questions[0]['audio'] = UploadedFile::fake()->create('notes.txt', 20, 'text/plain');

        $this->actingAs($admin)
            ->from(route('admin.quizzes.create'))
            ->post(route('admin.quizzes.store'), [
                'title' => 'Find the Note',
                'category' => 'Relative Pitch',
                'difficulty' => 'Beginner',
                'is_published' => true,
                'questions' => $questions,
            ])
            ->assertRedirect(route('admin.quizzes.create'))
            ->assertSessionHasErrors('questions.0.audio');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sampleQuestions(): array
    {
        return [
            [
                'prompt' => 'Which note is a perfect 5th above C?',
                'choices' => ['G', 'F', 'A', 'D'],
                'correct' => 0,
            ],
            [
                'prompt' => 'Which note is a major 3rd above A?',
                'choices' => ['C#', 'C', 'B', 'D'],
                'correct' => 0,
            ],
        ];
    }
}
