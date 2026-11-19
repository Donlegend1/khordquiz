<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupportMessageRequest;
use App\Models\SupportMessage;
use App\Services\SupportDesk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupportController extends Controller
{
    public const RECENT_REQUESTS = 20;

    /**
     * The Help & Support hub, with the member's own requests and their status.
     */
    public function index(Request $request): Response
    {
        $requests = SupportMessage::query()
            ->where('user_id', $request->user()->id)
            ->withCount('replies')
            ->orderByDesc('last_activity_at')
            ->orderByDesc('id')
            ->limit(self::RECENT_REQUESTS)
            ->get()
            ->map->toSummary();

        return Inertia::render('Member/Support', ['requests' => $requests]);
    }

    public function show(Request $request, SupportMessage $supportMessage): Response
    {
        abort_unless($supportMessage->user_id === $request->user()->id, 404);

        $supportMessage->load('replies.author');

        return Inertia::render('Member/SupportRequest', ['request' => $supportMessage->toThread()]);
    }

    /**
     * Opens a support request from Support → Email Support. The member's own
     * account supplies their name and email; device details, when they opt in,
     * are appended so the team can reproduce problems.
     */
    public function store(StoreSupportMessageRequest $request, SupportDesk $desk): RedirectResponse
    {
        $data = $request->validated();

        if ($request->boolean('include_device') && ! empty($data['device_info'])) {
            $data['message'] .= "\n\n---\n".$data['device_info'];
        }

        $supportMessage = $desk->open($request->user(), $data, 'web', $request->file('attachment'));

        return back()->with('success', __('Your message has been sent. We usually reply within 24 hours.'))
            ->with('supportRequestId', $supportMessage->id);
    }

    public function reply(Request $request, SupportMessage $supportMessage, SupportDesk $desk): RedirectResponse
    {
        abort_unless($supportMessage->user_id === $request->user()->id, 404);

        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $desk->replyAsMember($supportMessage, $request->user(), $data['body']);

        return back()->with('success', __('Your reply has been sent.'));
    }
}
