<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserPreference;
use App\Support\Locales;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'settings' => UserPreference::forUser($user)->toSettings($user),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validate([
            'appearance' => ['sometimes', 'string', Rule::in(UserPreference::APPEARANCES)],
            'haptics' => ['sometimes', 'boolean'],
            'language' => ['sometimes', 'string', Rule::in(Locales::labels())],
            'push_notifications' => ['sometimes', 'boolean'],
            'reminder_time' => ['sometimes', 'string', 'max:20'],
            'reminders' => ['sometimes', 'boolean'],
            'sound_effects' => ['sometimes', 'boolean'],
            'streak_alerts' => ['sometimes', 'boolean'],
            'default_instrument' => ['sometimes', 'string', Rule::in(UserPreference::INSTRUMENTS)],
            'daily_goal' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $preferences = UserPreference::forUser($user);
        $preferences->fill(collect($data)->except('daily_goal')->all());
        $preferences->save();

        // users.language is what the web UI's locale is driven from (SetLocale).
        $user->update(collect($data)->only(['daily_goal', 'language'])->all());

        return response()->json([
            'message' => 'Settings saved.',
            'settings' => $preferences->toSettings($user->fresh()),
        ]);
    }
}
