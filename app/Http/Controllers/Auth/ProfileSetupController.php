<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\QuizCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

// Shown once, right after signup (SignupController::store redirects here instead
// of straight to the dashboard) — mirrors the mobile app's "Set up your profile"
// onboarding screen (avatar, skill level, default language, default instrument),
// so a member who just started a trial or bought lifetime lands on the same
// personalization step either way, matching the mobile flow.
class ProfileSetupController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Auth/ProfileSetup', [
            'avatars' => User::AVATARS,
            'skillLevels' => User::SKILL_LEVELS,
            'languages' => QuizCatalog::LANGUAGES,
            'instruments' => QuizCatalog::allInstruments(),
            'user' => [
                'avatar' => $user->avatar,
                'skill_level' => $user->skill_level,
                'language' => $user->language,
                'default_instrument' => $user->default_instrument,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'avatar' => ['required', 'string', 'in:' . implode(',', User::AVATARS)],
            'skill_level' => ['required', 'string', 'in:' . implode(',', User::SKILL_LEVELS)],
            'language' => ['required', 'string', 'in:' . implode(',', QuizCatalog::LANGUAGES)],
            'default_instrument' => ['required', 'string', 'in:' . implode(',', QuizCatalog::allInstruments())],
        ]);

        $request->user()->update($data);

        return redirect()->route('dashboard');
    }
}
