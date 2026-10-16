<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\SupportMessage;
use App\Support\HelpCenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function help(Request $request): JsonResponse
    {
        $topic = $request->string('topic')->toString();
        $search = strtolower(trim($request->string('q')->toString()));
        $sections = HelpCenter::sections();

        if ($topic !== '') {
            $sections = array_values(array_filter($sections, fn ($section) => $section['title'] === $topic));
        }

        if ($search !== '') {
            $sections = array_values(array_filter(array_map(function (array $section) use ($search) {
                $section['faqs'] = array_values(array_filter(
                    $section['faqs'],
                    fn ($faq) => str_contains(strtolower($faq['question'].' '.$faq['answer']), $search),
                ));

                return $section;
            }, $sections), fn ($section) => $section['faqs'] !== []));
        }

        return response()->json([
            'topics' => array_column(HelpCenter::sections(), 'title'),
            'sections' => $sections,
        ]);
    }

    public function messages(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        if (! ChatMessage::query()->where('user_id', $userId)->exists()) {
            ChatMessage::query()->create([
                'user_id' => $userId,
                'sender' => 'agent',
                'text' => 'Hi there 👋 I’m here to help with anything about Khord Quiz. What can we help you with today?',
                'sent_at' => now(),
            ]);
        }

        return response()->json([
            'quick_replies' => [
                'I have a billing question',
                'A quiz isn’t working',
                'I need help with my account',
                'I have a suggestion',
            ],
            'messages' => ChatMessage::query()->where('user_id', $userId)->orderBy('id')->get()->map->toApi(),
        ]);
    }

    public function send(Request $request): JsonResponse
    {
        $text = $request->validate([
            'text' => ['required', 'string', 'max:2000'],
        ])['text'];

        $userId = $request->user()->id;
        $hasWritten = ChatMessage::query()->where('user_id', $userId)->where('sender', 'you')->exists();
        $created = [
            ChatMessage::query()->create([
                'user_id' => $userId,
                'sender' => 'you',
                'text' => trim($text),
                'sent_at' => now(),
            ]),
        ];

        if (! $hasWritten) {
            $created[] = ChatMessage::query()->create([
                'user_id' => $userId,
                'sender' => 'agent',
                'text' => 'Thanks for reaching out! A member of our team will reply here shortly. If it’s urgent, you can also email us at support@khordquiz.com.',
                'sent_at' => now(),
            ]);
        }

        return response()->json([
            'messages' => array_map(fn (ChatMessage $message) => $message->toApi(), $created),
        ], 201);
    }

    public function email(Request $request): JsonResponse
    {
        $data = $request->validate([
            'topic' => ['required', 'string', 'max:80'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $message = SupportMessage::query()->create([
            'user_id' => $request->user()->id,
            'topic' => $data['topic'],
            'message' => $data['message'],
        ]);

        return response()->json([
            'message' => 'Message sent. We reply within 24 hours.',
            'support_message' => [
                'id' => $message->id,
                'topic' => $message->topic,
                'message' => $message->message,
                'created_at' => $message->created_at?->toIso8601String(),
            ],
        ], 201);
    }
}
