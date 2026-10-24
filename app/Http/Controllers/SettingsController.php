<?php

namespace App\Http\Controllers;

use App\Models\QuizAttempt;
use App\Support\QuizCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Member/Settings', [
            'dailyGoal' => $user->daily_goal,
            'goalOptions' => ProgressController::GOAL_OPTIONS,
            'defaultInstrument' => $user->default_instrument,
        ]);
    }

    // The specific instrument (e.g. "EP") a member wants pre-selected on every
    // quiz's sound picker — see QuizController::resolveSound, which reads this
    // back to decide what a category selection actually resolves to.
    public function updateDefaultInstrument(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'default_instrument' => ['required', 'string', 'in:' . implode(',', QuizCatalog::allInstruments())],
        ]);

        $request->user()->update(['default_instrument' => $data['default_instrument']]);

        return back();
    }

    // Deletes real quiz history — everything else (preferences below, account,
    // plan) is either device-local or untouched, same as the mobile app's reset.
    public function resetProgress(Request $request): RedirectResponse
    {
        $user = $request->user();

        QuizAttempt::query()->where('user_id', $user->id)->delete();
        $user->update(['current_streak' => 0, 'last_practiced_on' => null]);

        return back()->with('success', 'Your progress has been reset.');
    }
}
