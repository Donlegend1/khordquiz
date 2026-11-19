<?php

namespace Tests\Feature;

use App\Models\QuizSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LeaderboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_the_leaderboard(): void
    {
        $this->get(route('leaderboard'))->assertRedirect(route('login'));
    }

    public function test_members_are_ranked_by_correct_answers_per_period(): void
    {
        $member = User::factory()->create(['name' => 'Ada Lovelace', 'current_streak' => 4]);
        $leader = User::factory()->create(['name' => 'Grace Hopper']);
        $veteran = User::factory()->create(['name' => 'Alan Turing']);

        $this->playSession($member, correct: 6, total: 8, playedAt: now());
        $this->playSession($leader, correct: 9, total: 10, playedAt: now()->subDays(2));
        $this->playSession($veteran, correct: 30, total: 40, playedAt: now()->subMonth());

        $this->actingAs($member)->get(route('leaderboard'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Member/Leaderboard')
                ->where('boards.week.total', 2)
                ->where('boards.week.top.0.name', 'Grace Hopper')
                ->where('boards.week.you.rank', 2)
                ->where('boards.week.you.points', 6)
                ->where('boards.week.you.accuracy', 75)
                ->where('boards.week.you.streak', 4)
                ->where('boards.all.total', 3)
                ->where('boards.all.top.0.name', 'Alan Turing')
                ->where('boards.all.you.rank', 3)
            );
    }

    public function test_members_who_have_not_played_still_get_a_rank(): void
    {
        $member = User::factory()->create();
        $this->playSession(User::factory()->create(), correct: 5, total: 5, playedAt: now());

        $this->actingAs($member)->get(route('leaderboard'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('boards.week.total', 2)
                ->where('boards.week.you.rank', 2)
                ->where('boards.week.you.points', 0)
                ->where('boards.week.you.is_you', true)
            );
    }

    private function playSession(User $user, int $correct, int $total, mixed $playedAt): void
    {
        QuizSession::query()->create([
            'user_id' => $user->id,
            'slug' => 'intervals',
            'correct' => $correct,
            'total' => $total,
            'played_at' => $playedAt,
        ]);
    }
}
