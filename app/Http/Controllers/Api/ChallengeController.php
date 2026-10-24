<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\User;
use App\Support\MobileCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChallengeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $challenges = Challenge::query()
            ->where('user_id', $request->user()->id)
            ->with('results')
            ->latest('sent_at')
            ->get()
            ->map(fn (Challenge $challenge) => $this->row($challenge, $request->user()));

        return response()->json(['challenges' => $challenges]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slug' => ['required', 'string'],
            'score' => ['required', 'integer', 'min:0'],
            'total' => ['required', 'integer', 'min:1', 'max:'.MobileCatalog::QUIZ_LENGTH],
        ]);

        $quiz = MobileCatalog::find($data['slug']);
        abort_if($quiz === null, 422, 'That quiz is not in the catalog.');
        abort_if($data['score'] > $data['total'], 422, 'Score cannot exceed the total.');

        /** @var User $user */
        $user = $request->user();
        $challenge = Challenge::query()->create([
            'user_id' => $user->id,
            'mine' => true,
            'slug' => $quiz['slug'],
            'total' => $data['total'],
            'from_name' => $this->displayName($user),
            'from_avatar' => $user->avatar,
            'from_score' => $data['score'],
            'sent_at' => now(),
        ]);

        return response()->json([
            'message' => 'Challenge created.',
            'share_url' => url('/c/'.$challenge->id),
            'challenge' => $challenge->load('results')->toApi() + ['quiz' => $quiz],
        ], 201);
    }

    public function complete(Request $request, Challenge $challenge): JsonResponse
    {
        abort_unless($challenge->user_id === $request->user()->id, 404);
        abort_if($challenge->mine, 422, 'Play a challenge that was sent to you.');

        $data = $request->validate([
            'score' => ['required', 'integer', 'min:0', 'max:'.$challenge->total],
        ]);

        /** @var User $user */
        $user = $request->user();
        $challenge->results()->delete();
        $challenge->results()->create([
            'name' => $this->displayName($user),
            'avatar' => $user->avatar,
            'score' => $data['score'],
        ]);

        return response()->json([
            'message' => 'Score saved.',
            'challenge' => $this->row($challenge->fresh('results'), $user),
        ]);
    }

    public function result(Request $request, Challenge $challenge): JsonResponse
    {
        abort_unless($challenge->user_id === $request->user()->id, 404);
        abort_unless($challenge->mine, 422, 'Friend scores belong on a challenge you sent.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'avatar' => ['nullable', 'string', 'max:50'],
            'score' => ['required', 'integer', 'min:0', 'max:'.$challenge->total],
        ]);

        $challenge->results()->create($data);

        return response()->json([
            'message' => 'Result recorded.',
            'challenge' => $this->row($challenge->fresh('results'), $request->user()),
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Challenge $challenge, User $user): array
    {
        $payload = $challenge->toApi();
        $quiz = MobileCatalog::find($challenge->slug);
        $youScore = $challenge->mine ? $challenge->from_score : ($challenge->results->first()->score ?? null);
        $opponent = $challenge->mine ? $challenge->results->first() : null;
        $theirScore = $challenge->mine ? $opponent?->score : $challenge->from_score;

        $status = 'waiting';
        if (! $challenge->mine && $challenge->results->isEmpty()) {
            $status = 'your-turn';
        } elseif ($youScore !== null && $theirScore !== null) {
            $status = $youScore > $theirScore ? 'won' : ($youScore < $theirScore ? 'lost' : 'draw');
        }

        return $payload + [
            'quiz' => $quiz,
            'status' => $challenge->mine && $challenge->results->count() > 1 ? 'played' : $status,
            'you' => [
                'name' => $this->displayName($user),
                'avatar' => $user->avatar,
                'score' => $youScore ?? 0,
            ],
        ];
    }

    private function displayName(User $user): string
    {
        if ($user->first_name) {
            $last = $user->last_name ? ' '.mb_substr($user->last_name, 0, 1).'.' : '';

            return $user->first_name.$last;
        }

        return $user->name;
    }
}
