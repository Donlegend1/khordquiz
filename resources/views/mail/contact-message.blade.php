New message from the KhordQuiz website contact form.

From: {{ $contactMessage->name }} <{{ $contactMessage->email }}>
Subject: {{ $contactMessage->subject }}
Sent: {{ $contactMessage->created_at->toDayDateTimeString() }}

{{ $contactMessage->message }}
