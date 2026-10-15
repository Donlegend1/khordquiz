<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupportMessageRequest;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SupportController extends Controller
{
    /**
     * Save a member's support request and email it to support — same
     * ContactMessage/Mailable pipeline the public Contact form uses, just
     * with the member's own account for name/email instead of asking for it,
     * and an optional file attachment the public form doesn't have.
     */
    public function store(StoreSupportMessageRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $attachmentPath = null;
        $attachmentName = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentPath = $file->store('support-attachments', 'public');
            $attachmentName = $file->getClientOriginalName();
        }

        $message = $data['message'];
        if ($request->boolean('include_device') && ! empty($data['device_info'])) {
            $message .= "\n\n---\n".$data['device_info'];
        }

        $contactMessage = ContactMessage::create([
            'name' => $user->name,
            'email' => $user->email,
            'subject' => '['.$data['topic'].'] '.($data['subject'] ?: 'Support request'),
            'message' => $message,
            'ip_address' => $request->ip(),
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
        ]);

        // The message is already saved, so a mail failure shouldn't fail the member's request.
        try {
            Mail::to(config('mail.contact_address'))->send(new ContactMessageReceived($contactMessage));
        } catch (Throwable $e) {
            Log::error('Support message email failed', ['id' => $contactMessage->id, 'error' => $e->getMessage()]);
        }

        return back()->with('success', 'Your message has been sent. We usually reply within 24 hours.');
    }
}
