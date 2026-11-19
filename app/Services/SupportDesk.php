<?php

namespace App\Services;

use App\Mail\SupportRequestReceived;
use App\Models\SupportMessage;
use App\Models\SupportReply;
use App\Models\User;
use App\Notifications\SupportReplied;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Everything that happens to a support request, for both the web and the
 * mobile app: opening it, replies from either side, and resolving. The team
 * hears about member activity by email; members hear about team replies
 * through a notification (in-app, plus email if they chose it in Settings).
 */
class SupportDesk
{
    /**
     * @param  array{topic: string, subject?: ?string, message: string}  $data
     */
    public function open(User $member, array $data, string $source, ?UploadedFile $attachment = null): SupportMessage
    {
        $supportMessage = SupportMessage::query()->create([
            'user_id' => $member->id,
            'topic' => $data['topic'],
            'subject' => ($data['subject'] ?? null) ?: null,
            'message' => $data['message'],
            'source' => $source,
            'attachment_path' => $attachment?->store('support-attachments', 'public'),
            'attachment_name' => $attachment?->getClientOriginalName(),
            'status' => SupportMessage::STATUS_OPEN,
            'last_activity_at' => now(),
        ]);

        $this->emailTeam($supportMessage);

        return $supportMessage;
    }

    public function replyAsMember(SupportMessage $supportMessage, User $member, string $body): SupportReply
    {
        $reply = $supportMessage->replies()->create(['user_id' => $member->id, 'from_staff' => false, 'body' => trim($body)]);
        $supportMessage->update(['status' => SupportMessage::STATUS_OPEN, 'last_activity_at' => now(), 'resolved_at' => null]);

        $this->emailTeam($supportMessage, $reply);

        return $reply;
    }

    public function replyAsStaff(SupportMessage $supportMessage, User $admin, string $body, bool $resolve = false): SupportReply
    {
        $reply = $supportMessage->replies()->create(['user_id' => $admin->id, 'from_staff' => true, 'body' => trim($body)]);
        $supportMessage->update([
            'status' => $resolve ? SupportMessage::STATUS_RESOLVED : SupportMessage::STATUS_ANSWERED,
            'last_activity_at' => now(),
            'resolved_at' => $resolve ? now() : null,
        ]);

        $supportMessage->user?->notify(new SupportReplied($supportMessage));

        return $reply;
    }

    public function setStatus(SupportMessage $supportMessage, string $status): void
    {
        $supportMessage->update([
            'status' => $status,
            'resolved_at' => $status === SupportMessage::STATUS_RESOLVED ? now() : null,
        ]);
    }

    /**
     * The request (or reply) is already saved, so a mail failure is logged
     * rather than failing the member's request.
     */
    private function emailTeam(SupportMessage $supportMessage, ?SupportReply $reply = null): void
    {
        try {
            Mail::to(config('mail.contact_address'))->send(new SupportRequestReceived($supportMessage, $reply));
        } catch (Throwable $e) {
            Log::error('Support email to the team failed.', ['support_message' => $supportMessage->id, 'error' => $e->getMessage()]);
        }
    }
}
