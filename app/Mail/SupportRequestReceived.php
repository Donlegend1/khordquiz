<?php

namespace App\Mail;

use App\Models\SupportMessage;
use App\Models\SupportReply;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the support mailbox about a new request, or a member's follow-up on
 * one ($reply). Replying by email goes straight to the member; the admin link
 * answers inside KhordQuiz so the member sees it in their thread.
 */
class SupportRequestReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SupportMessage $supportMessage, public ?SupportReply $reply = null) {}

    public function envelope(): Envelope
    {
        $member = $this->supportMessage->user;

        return new Envelope(
            replyTo: $member ? [new Address($member->email, $member->name)] : [],
            subject: ($this->reply ? 'Re: ' : '').'[Support #'.$this->supportMessage->id.'] ['.$this->supportMessage->topic.'] '.$this->supportMessage->title(),
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.support-request',
            with: ['adminUrl' => route('admin.support.show', $this->supportMessage)],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if ($this->reply || ! $this->supportMessage->attachment_path) {
            return [];
        }

        return [
            Attachment::fromStorageDisk('public', $this->supportMessage->attachment_path)
                ->as($this->supportMessage->attachment_name ?? basename($this->supportMessage->attachment_path)),
        ];
    }
}
