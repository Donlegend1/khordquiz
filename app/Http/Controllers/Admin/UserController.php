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
        $payment = $request->string('payment')->toString();
        $sort = $request->string('sort', 'latest')->toString();

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
            // Same "no real billing system" caveat as trial/in-app below — there's no
            // cancellation field anywhere in the schema (no subscriptions table to cancel
            // a row in), so this always matches zero members until one exists.
            ->when($status === 'canceled', fn ($query) => $query->whereRaw('1 = 0'))
            // There's no real billing system, so "active subscription" isn't a tracked fact —
            // the closest honest signal is whether the member ever stated a plan choice at all.
            ->when($plan === 'inactive', fn ($query) => $query->whereNull('plan_choice'))
            ->when(in_array($plan, ['monthly', 'yearly', 'lifetime'], true), fn ($query) => $query->where('plan_choice', $plan))
            // There's no trial-tracking field anywhere in the schema yet (no real billing
            // system to drive one from) — this always matches zero members until one exists,
            // same as 'in-app' below, rather than silently dropping the option from the UI.
            ->when($plan === 'trial', fn ($query) => $query->whereRaw('1 = 0'))
            ->when(in_array($payment, ['stripe', 'paypal'], true), fn ($query) => $query->where('payment_method', $payment))
            // The mobile app has no backend connection to this database yet, so an
            // App Store/Google Play purchase can never be recorded here — same honest
            // "always empty for now" treatment as the trial filter above.
            ->when($payment === 'in-app', fn ($query) => $query->whereRaw('1 = 0'))
            ->orderBy('created_at', $sort === 'oldest' ? 'asc' : 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user) => $this->summary($user));

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => ['search' => $search, 'role' => $role, 'status' => $status, 'plan' => $plan, 'payment' => $payment, 'sort' => $sort],
            'stats' => [
                'total' => User::query()->count(),
                // Same "no real billing system" caveat as the plan=inactive filter above —
                // active/inactive here means "has ever stated a plan choice," not "has a
                // currently-paid subscription."
                'activeMembers' => User::query()->whereNotNull('plan_choice')->count(),
                'inactiveMembers' => User::query()->whereNull('plan_choice')->count(),
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

    // Sets (or replaces) a member's plan, payment method and expiry — an admin override
    // for manual grants, comps and support cases, not a billing event. plan_started_at is
    // only set the first time a plan is assigned, so re-granting the same plan (e.g. after
    // an extension) doesn't reset "member since."
    public function updatePlan(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'plan_choice' => ['required', 'string', 'in:monthly,yearly,lifetime'],
            'payment_method' => ['nullable', 'string', 'in:stripe,paypal'],
            'plan_expires_at' => ['nullable', 'date'],
        ]);

        $user->update([
            'plan_choice' => $data['plan_choice'],
            'payment_method' => $data['payment_method'] ?? $user->payment_method,
            'plan_started_at' => $user->plan_started_at ?? now(),
            'plan_expires_at' => $data['plan_choice'] === 'lifetime' ? null : ($data['plan_expires_at'] ?? null),
        ]);

        return back()->with('success', "{$user->name}'s plan was updated.");
    }

    // Pushes the expiry date forward by a fixed number of days from whichever is later —
    // the current expiry, or today (so extending an already-expired plan starts counting
    // from now, not from a date that's already passed).
    public function extendPlan(Request $request, User $user): RedirectResponse
    {
        abort_if($user->plan_choice === null, 422, "Assign a plan to {$user->name} before extending it.");
        abort_if($user->plan_choice === 'lifetime', 422, 'A lifetime plan has no expiry to extend.');

        $data = $request->validate(['days' => ['required', 'integer', 'in:7,30,90,365']]);

        $base = $user->plan_expires_at && $user->plan_expires_at->isFuture() ? $user->plan_expires_at : now();
        $user->update(['plan_expires_at' => $base->copy()->addDays($data['days'])]);

        return back()->with('success', "{$user->name}'s plan was extended by {$data['days']} days.");
    }

    public function cancelPlan(User $user): RedirectResponse
    {
        $user->update(['plan_choice' => null, 'plan_started_at' => null, 'plan_expires_at' => null]);

        return back()->with('success', "{$user->name}'s plan was cancelled.");
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
            'payment_method' => $user->payment_method,
            'plan_started_at' => $user->plan_started_at?->toFormattedDateString(),
            'plan_expires_at' => $user->plan_expires_at?->toFormattedDateString(),
            'plan_expired' => $user->hasPlanExpired(),
            'quizzes_attempted' => $user->quizzes_attempted_count,
            'quizzes_completed' => $user->quizzes_completed_count,
            'joined_at' => $user->created_at->toFormattedDateString(),
            'joined_ago' => $user->created_at->diffForHumans(),
        ];
    }
}
