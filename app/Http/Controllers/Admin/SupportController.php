<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Services\SupportDesk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The admin Support inbox: every request from the web and the mobile app,
 * newest activity first, with replies and status changes.
 */
class SupportController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(SupportMessage::STATUSES)],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $status = $filters['status'] ?? '';
        $search = trim($filters['search'] ?? '');

        $requests = SupportMessage::query()
            ->with('user:id,name,email')
            ->withCount('replies')
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('subject', 'like', '%'.$search.'%')
                        ->orWhere('topic', 'like', '%'.$search.'%')
                        ->orWhere('message', 'like', '%'.$search.'%')
                        ->orWhere('id', $search)
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'));
                });
            })
            ->orderByDesc('last_activity_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (SupportMessage $supportMessage) => [
                ...$supportMessage->toSummary(),
                'member' => $supportMessage->user?->only(['id', 'name', 'email']),
            ]);

        $counts = SupportMessage::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return Inertia::render('Admin/Support/Index', [
            'requests' => $requests,
            'filters' => ['status' => $status, 'search' => $search],
            'counts' => [
                'all' => (int) $counts->sum(),
                ...collect(SupportMessage::STATUSES)->mapWithKeys(fn (string $key) => [$key => (int) ($counts[$key] ?? 0)])->all(),
            ],
        ]);
    }

    public function show(SupportMessage $supportMessage): Response
    {
        $supportMessage->load(['user:id,name,email,created_at', 'replies.author']);

        return Inertia::render('Admin/Support/Show', [
            'request' => [
                ...$supportMessage->toThread(),
                'member' => $supportMessage->user?->only(['id', 'name', 'email']),
            ],
        ]);
    }

    public function reply(Request $request, SupportMessage $supportMessage, SupportDesk $desk): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'resolve' => ['boolean'],
        ]);

        $desk->replyAsStaff($supportMessage, $request->user(), $data['body'], $request->boolean('resolve'));

        return back()->with('success', 'Reply sent. The member has been notified.');
    }

    public function updateStatus(Request $request, SupportMessage $supportMessage, SupportDesk $desk): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in([SupportMessage::STATUS_OPEN, SupportMessage::STATUS_RESOLVED])]]);
        $desk->setStatus($supportMessage, $data['status']);

        return back()->with('success', $data['status'] === SupportMessage::STATUS_RESOLVED ? 'Request resolved.' : 'Request reopened.');
    }
}
