<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public const LIMIT = 100;

    public function index(Request $request): Response
    {
        $notifications = $request->user()->notifications()
            ->latest()
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (DatabaseNotification $notification): array => [
                'id' => $notification->id,
                'kind' => $notification->data['kind'] ?? 'general',
                'title' => $notification->data['title'] ?? '',
                'body' => $notification->data['body'] ?? '',
                'params' => $notification->data['params'] ?? [],
                'url' => $notification->data['url'] ?? null,
                'at' => $notification->created_at->toIso8601String(),
                'read' => $notification->read_at !== null,
            ]);

        return Inertia::render('Member/Notifications', ['notifications' => $notifications]);
    }

    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $request->user()->unreadNotifications()->whereKey($notification)->update(['read_at' => now()]);

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }
}
