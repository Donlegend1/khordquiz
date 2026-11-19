<?php

namespace App\Http\Controllers;

use App\Services\Leaderboard;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaderboardController extends Controller
{
    /**
     * Both periods are sent up front so switching tabs needs no round trip.
     */
    public function __invoke(Request $request, Leaderboard $leaderboard): Response
    {
        $boards = [];
        foreach (Leaderboard::PERIODS as $period) {
            $boards[$period] = $leaderboard->forUser($request->user(), $period);
        }

        return Inertia::render('Member/Leaderboard', ['boards' => $boards]);
    }
}
