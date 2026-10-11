<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Member/EditProfile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'skillLevels' => User::SKILL_LEVELS,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        // Email is locked from the Edit Profile form and isn't a validated
        // field here, so a crafted request can't smuggle a new one through.
        $request->user()->fill($request->validated());
        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Download everything this member's account holds, as JSON — the
     * self-service side of the Privacy Policy's data-portability promise.
     */
    public function export(Request $request): HttpResponse
    {
        $user = $request->user();

        $data = [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'country' => $user->country,
                'skill_level' => $user->skill_level,
                'daily_goal' => $user->daily_goal,
                'current_streak' => $user->current_streak,
                'longest_streak' => $user->longest_streak,
                'last_practiced_on' => $user->last_practiced_on?->toDateString(),
                'account_created_at' => $user->created_at?->toIso8601String(),
                'terms_accepted_at' => $user->terms_accepted_at?->toIso8601String(),
            ],
            'quiz_attempts' => $user->attempts()->with('quiz:id,title,category')->get()->map(fn ($attempt) => [
                'quiz_title' => $attempt->quiz?->title,
                'quiz_category' => $attempt->quiz?->category,
                'status' => $attempt->status,
                'stopped_at_question' => $attempt->stopped_at_question,
                'correct_answers' => $attempt->correct_answers,
                'last_played_at' => $attempt->last_played_at?->toIso8601String(),
                'completed_at' => $attempt->completed_at?->toIso8601String(),
            ]),
            'awards' => $user->awards()->get()->map(fn ($award) => [
                'title' => $award->title,
                'description' => $award->description,
                'kind' => $award->kind,
                'earned_at' => $award->earned_at?->toIso8601String(),
            ]),
        ];

        $filename = 'khordquiz-data-export-'.now()->format('Y-m-d').'.json';

        return response(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
