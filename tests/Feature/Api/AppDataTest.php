<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AppDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_plans_and_help_match_the_app(): void
    {
        $catalog = $this->getJson('/api/v1/catalog')->assertOk()->json();

        $this->assertSame(25, $catalog['quiz_length']);
        $this->assertSame('Relative Pitch', $catalog['categories'][0]['name']);
        $this->assertTrue(collect($catalog['categories'][0]['quizzes'])->contains(
            fn ($quiz) => $quiz['slug'] === 'Relative Tone Pitch no1' && $quiz['name'] === 'Relative Tone Pitch #1',
        ));

        $this->getJson('/api/v1/quizzes/'.rawurlencode('Relative Tone Pitch no1'))
            ->assertOk()
            ->assertJsonPath('quiz.question_count', 25);

        $this->getJson('/api/v1/plans')
            ->assertOk()
            ->assertJsonPath('plans.1.price', '$49.99/year')
            ->assertJsonPath('trial_days', 7);

        $this->getJson('/api/v1/help')
            ->assertOk()
            ->assertJsonPath('sections.0.title', 'Getting started');
    }

    public function test_a_student_can_practice_and_see_it_on_home_and_progress(): void
    {
        $user = User::factory()->create(['first_name' => 'Kings', 'daily_goal' => 20]);
        Sanctum::actingAs($user);

        $slug = rawurlencode('3-Note Melody');

        $this->postJson('/api/v1/quizzes/'.$slug.'/bookmark')->assertOk()->assertJsonPath('bookmarked', true);
        $this->getJson('/api/v1/saved')->assertOk()->assertJsonPath('quizzes.0.name', '3-Note Melody');

        $this->postJson('/api/v1/quizzes/'.$slug.'/sessions', [
            'correct' => 20,
            'total' => 25,
            'completed' => true,
            'answers' => [
                ['answer' => 'C', 'picked' => 'D', 'correct' => false, 'kind' => 'single'],
                ['answer' => 'E', 'picked' => 'E', 'correct' => true, 'kind' => 'single'],
            ],
        ])->assertCreated()->assertJsonPath('accuracy', 80);

        $this->getJson('/api/v1/home')
            ->assertOk()
            ->assertJsonPath('greeting_name', 'Kings')
            ->assertJsonPath('daily_goal.target', 20)
            ->assertJsonPath('saved_count', 1);

        $this->getJson('/api/v1/progress')
            ->assertOk()
            ->assertJsonPath('overall.total', 2)
            ->assertJsonPath('goal.done', 2);

        $this->getJson('/api/v1/mistakes')
            ->assertOk()
            ->assertJsonPath('mistakes.0.picked', 'D');

        $this->getJson('/api/v1/leaderboard?period=all')
            ->assertOk()
            ->assertJsonPath('you.is_you', true)
            ->assertJsonPath('you.points', 20);

        $this->getJson('/api/v1/suggestions')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Video explanations for failed questions']);
    }

    public function test_suggestions_settings_subscription_and_chat(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $created = $this->postJson('/api/v1/suggestions', [
            'title' => 'Slower metronome',
            'description' => 'Let me slow the click track while I practise.',
        ])->assertCreated();

        $this->postJson('/api/v1/suggestions/'.$created->json('suggestion.id').'/vote')
            ->assertOk()
            ->assertJsonPath('suggestion.voted', false)
            ->assertJsonPath('suggestion.votes', 0);

        $this->patchJson('/api/v1/settings', [
            'appearance' => 'Dark',
            'default_instrument' => 'Guitar',
            'daily_goal' => 10,
        ])->assertOk()
            ->assertJsonPath('settings.appearance', 'Dark')
            ->assertJsonPath('settings.daily_goal', 10);

        $this->postJson('/api/v1/subscription/trial', ['plan_id' => 'yearly'])
            ->assertOk()
            ->assertJsonPath('subscription.access', 'trial')
            ->assertJsonPath('subscription.unlocked', true);

        $this->getJson('/api/v1/chat')
            ->assertOk()
            ->assertJsonPath('messages.0.from', 'agent');

        $this->postJson('/api/v1/chat', ['text' => 'I need help with a quiz'])
            ->assertCreated()
            ->assertJsonPath('messages.1.from', 'agent');

        $challenge = $this->postJson('/api/v1/challenges', [
            'slug' => '3-Note Melody',
            'score' => 18,
            'total' => 25,
        ])->assertCreated();

        $this->postJson('/api/v1/challenges/'.$challenge->json('challenge.id').'/results', [
            'name' => 'Tunde B.',
            'avatar' => 'electric-guitar',
            'score' => 23,
        ])->assertCreated()->assertJsonPath('challenge.results.0.name', 'Tunde B.');
    }
}
