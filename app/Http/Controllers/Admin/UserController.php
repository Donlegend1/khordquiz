<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use App\Models\QuizMark;
use App\Models\User;
use App\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $role = $request->string('role')->toString();
        $status = $request->string('status')->toString();
        $plan = $request->string('plan')->toString();

        $users = User::query()
            ->withCount([
                'attempts as quizzes_attempted_count',
                'attempts as quizzes_completed_count' => fn ($query) => $query->where('status', QuizAttempt::STATUS_COMPLETED),
            ])
            ->when($search !== '', fn ($query) => $query->where(
                fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")
            ))
            ->when($role === 'admin', fn ($query) => $query->where('role', Role::Admin))
            ->when($role === 'member', fn ($query) => $query->where('role', Role::Member))
            ->when($status === 'suspended', fn ($query) => $query->whereNotNull('suspended_at'))
            ->when($status === 'active', fn ($query) => $query->whereNull('suspended_at'))
            // There's no real billing system, so "active subscription" isn't a tracked fact —
            // the closest honest signal is whether the member ever stated a plan choice at all.
            ->when($plan === 'inactive', fn ($query) => $query->whereNull('plan_choice'))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user) => $this->summary($user));

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => ['search' => $search, 'role' => $role, 'status' => $status, 'plan' => $plan],
            'stats' => [
                'total' => User::query()->count(),
                'admins' => User::query()->where('role', Role::Admin)->count(),
                'suspended' => User::query()->whereNotNull('suspended_at')->count(),
                'newThisWeek' => User::query()->where('created_at', '>=', now()->subDays(7))->count(),
            ],
        ]);
    }

    public function show(User $user): Response
    {
        $completedAttempts = QuizAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', QuizAttempt::STATUS_COMPLETED)
            ->with('quiz')
            ->latest('completed_at')
            ->get();

        $accuracy = null;
        if ($completedAttempts->isNotEmpty()) {
            $accuracy = (int) round(
                $completedAttempts->sum(fn (QuizAttempt $attempt) => $attempt->correct_answers / max(1, $attempt->quiz->question_count)) / $completedAttempts->count() * 100
            );
        }

        return Inertia::render('Admin/Users/Show', [
            'member' => $this->summary($user->loadCount([
                'attempts as quizzes_attempted_count',
                'attempts as quizzes_completed_count' => fn ($query) => $query->where('status', QuizAttempt::STATUS_COMPLETED),
            ])) + [
                'accuracy' => $accuracy,
                'saved_count' => QuizMark::query()->where('user_id', $user->id)->where('bookmarked', true)->count(),
            ],
            'recentAttempts' => $completedAttempts->take(10)->map(fn (QuizAttempt $attempt) => [
                'id' => $attempt->id,
                'quiz_title' => $attempt->quiz->title,
                'score' => (int) round($attempt->correct_answers / max(1, $attempt->quiz->question_count) * 100),
                'completed_at' => $attempt->completed_at?->diffForHumans(),
            ])->values(),
            'awards' => $user->awards()->latest('earned_at')->get(['title', 'description', 'earned_at'])->map(fn ($award) => [
                'title' => $award->title,
                'description' => $award->description,
                'earned_at' => $award->earned_at->diffForHumans(),
            ])->values(),
        ]);
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        $this->guardSelf($request, $user, 'suspend');

        $user->update(['suspended_at' => now()]);

        return back()->with('success', "{$user->name} has been suspended.");
    }

    public function unsuspend(User $user): RedirectResponse
    {
        $user->update(['suspended_at' => null]);

        return back()->with('success', "{$user->name} is no longer suspended.");
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $this->guardSelf($request, $user, 'change the role of');

        $data = $request->validate(['role' => ['required', 'string', 'in:admin,member']]);

        $user->update(['role' => Role::from($data['role'])]);

        return back()->with('success', "{$user->name} is now " . ($data['role'] === 'admin' ? 'an admin.' : 'a member.'));
    }

    private function guardSelf(Request $request, User $user, string $action): void
    {
        abort_if($request->user()->id === $user->id, 403, "You can't {$action} your own account.");
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'suspended' => $user->isSuspended(),
            'country' => $user->country,
            'skill_level' => $user->skill_level,
            'current_streak' => $user->current_streak,
            'longest_streak' => $user->longest_streak,
            'last_practiced_on' => $user->last_practiced_on?->toFormattedDateString(),
            'plan_choice' => $user->plan_choice,
            'quizzes_attempted' => $user->quizzes_attempted_count,
            'quizzes_completed' => $user->quizzes_completed_count,
            'joined_at' => $user->created_at->toFormattedDateString(),
            'joined_ago' => $user->created_at->diffForHumans(),
        ];
    }
}
