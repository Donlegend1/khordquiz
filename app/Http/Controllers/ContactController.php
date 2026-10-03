<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ContactController extends Controller
{
    /**
     * Save a contact form message and email it to support.
     */
    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        $contactMessage = ContactMessage::create([
            ...$request->safe()->only(['name', 'email', 'subject', 'message']),
            'ip_address' => $request->ip(),
        ]);

        // The message is already saved, so a mail failure shouldn't fail the visitor's request.
        try {
            Mail::to(config('mail.contact_address'))->send(new ContactMessageReceived($contactMessage));
        } catch (Throwable $e) {
            Log::error('Contact message email failed', ['id' => $contactMessage->id, 'error' => $e->getMessage()]);
        }

        return back();
    }
}
