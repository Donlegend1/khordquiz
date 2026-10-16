<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserPreference;
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
            'language' => ['sometimes', 'string', 'max:50'],
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

        if (array_key_exists('daily_goal', $data)) {
            $user->update(['daily_goal' => $data['daily_goal']]);
        }

        return response()->json([
            'message' => 'Settings saved.',
            'settings' => $preferences->toSettings($user->fresh()),
        ]);
    }
}
