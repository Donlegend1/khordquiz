<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Suggestion;
use App\Models\SuggestionVote;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuggestionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $suggestions = Suggestion::query()
            ->withExists(['votes as voted' => fn ($query) => $query->where('user_id', $userId)])
            ->latest('id')
            ->get()
            ->map(fn (Suggestion $suggestion) => $suggestion->toApi((bool) $suggestion->voted));

        return response()->json(['suggestions' => $suggestions]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:1000'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $suggestion = Suggestion::query()->create([
            'user_id' => $user->id,
            'author' => $user->name,
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => 'pending',
            'votes' => 1,
        ]);
        SuggestionVote::query()->create([
            'suggestion_id' => $suggestion->id,
            'user_id' => $user->id,
        ]);

        return response()->json([
            'message' => 'Suggestion submitted.',
            'suggestion' => $suggestion->toApi(true),
        ], 201);
    }

    public function vote(Request $request, Suggestion $suggestion): JsonResponse
    {
        $vote = SuggestionVote::query()->where('suggestion_id', $suggestion->id)->where('user_id', $request->user()->id);

        if ($vote->exists()) {
            $vote->delete();
            $suggestion->decrement('votes');
            $voted = false;
        } else {
            SuggestionVote::query()->create([
                'suggestion_id' => $suggestion->id,
                'user_id' => $request->user()->id,
            ]);
            $suggestion->increment('votes');
            $voted = true;
        }

        return response()->json([
            'suggestion' => $suggestion->fresh()->toApi($voted),
        ]);
    }
}
