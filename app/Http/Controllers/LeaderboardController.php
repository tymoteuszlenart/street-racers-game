<?php

namespace App\Http\Controllers;

use App\Models\PlayerProfile;
use Illuminate\View\View;

class LeaderboardController extends Controller
{
    public function index(): View
    {
        $profiles = PlayerProfile::query()
            ->with('user:id,name')
            ->orderByDesc('reputation')
            ->orderByDesc('level')
            ->orderBy('user_id')
            ->paginate(50);

        $rankOffset = ($profiles->currentPage() - 1) * $profiles->perPage();

        return view('leaderboard.index', [
            'profiles' => $profiles,
            'rankOffset' => $rankOffset,
            'currentUserId' => auth()->id(),
        ]);
    }
}
