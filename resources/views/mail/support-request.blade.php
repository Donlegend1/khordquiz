@if ($reply)
{{ $supportMessage->user?->name ?? 'A member' }} replied to support request #{{ $supportMessage->id }}.
@else
New support request #{{ $supportMessage->id }} from the KhordQuiz {{ $supportMessage->source === 'web' ? 'website' : 'app' }}.
@endif

From: {{ $supportMessage->user?->name }} <{{ $supportMessage->user?->email }}>
Topic: {{ $supportMessage->topic }}
Subject: {{ $supportMessage->title() }}
Sent: {{ ($reply?->created_at ?? $supportMessage->created_at)->toDayDateTimeString() }}

{{ $reply?->body ?? $supportMessage->message }}

Reply in KhordQuiz so the member sees it in their support thread:
{{ $adminUrl }}
