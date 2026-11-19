<?php

namespace App\Http\Controllers;

use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizSession;
use App\Models\UserPreference;
use App\Support\Locales;
use App\Support\QuizCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Validation\Rule;
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
            'language' => $user->language ?? Locales::DEFAULT_LABEL,
            'preferences' => collect(UserPreference::forUser($user)->toSettings($user))
                ->only(['appearance', 'notation', 'sound_effects', 'haptics', 'reminders', 'reminder_time', 'streak_alerts', 'notify_by']),
        ]);
    }

    /**
     * Saves any of the toggles and pickers that live in user_preferences (the
     * same row the mobile app's settings screen reads), one field at a time.
     */
    public function updatePreferences(Request $request): RedirectResponse|HttpResponse
    {
        $data = $request->validate([
            'appearance' => ['sometimes', 'string', Rule::in(UserPreference::APPEARANCES)],
            'notation' => ['sometimes', 'string', Rule::in(UserPreference::NOTATIONS)],
            'sound_effects' => ['sometimes', 'boolean'],
            'haptics' => ['sometimes', 'boolean'],
            'reminders' => ['sometimes', 'boolean'],
            'reminder_time' => ['sometimes', 'string', 'regex:/^(1[0-2]|[1-9]):[0-5]\d (AM|PM)$/'],
            'streak_alerts' => ['sometimes', 'boolean'],
            'notify_by' => ['sometimes', 'string', Rule::in(UserPreference::NOTIFY_BY)],
            'timezone' => ['sometimes', 'nullable', 'timezone:all'],
        ]);

        $preferences = UserPreference::forUser($request->user());
        $preferences->fill($data);
        $preferences->syncPushFlag();
        $preferences->save();

        // The app shell reports the browser's timezone in the background with fetch().
        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->noContent();
        }

        return back();
    }

    // Switches the whole UI to the chosen language — SetLocale reads
    // users.language on every request. Mirrored to user_preferences so the
    // mobile app's settings screen shows the same pick.
    public function updateLanguage(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'language' => ['required', 'string', 'in:'.implode(',', Locales::labels())],
        ]);

        $user = $request->user();
        $user->update(['language' => $data['language']]);
        UserPreference::forUser($user)->update(['language' => $data['language']]);

        return back();
    }

    // The specific instrument (e.g. "EP") a member wants pre-selected on every
    // quiz's sound picker — see QuizController::resolveSound, which reads this
    // back to decide what a category selection actually resolves to.
    public function updateDefaultInstrument(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'default_instrument' => ['required', 'string', 'in:'.implode(',', QuizCatalog::allInstruments())],
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
        QuizSession::query()->where('user_id', $user->id)->delete();
        QuizAnswer::query()->where('user_id', $user->id)->delete();
        $user->update(['current_streak' => 0, 'last_practiced_on' => null]);

        return back()->with('success', __('Your progress has been reset.'));
    }
}
