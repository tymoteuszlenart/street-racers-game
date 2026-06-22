<?php

namespace App\Http\Controllers;

use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\View\View;

class LeaderboardController extends Controller
{
    public function index(): View
    {
        $currentProfile = auth()->user()?->playerProfile;

        $adminUserIds = User::query()->where('is_admin', true)->pluck('id');

        $profiles = PlayerProfile::query()
            ->with('user:id,name')
            ->whereNotIn('user_id', $adminUserIds)
            ->orderByDesc('reputation')
            ->orderByDesc('level')
            ->orderBy('user_id')
            ->paginate(50);

        $rankOffset = ($profiles->currentPage() - 1) * $profiles->perPage();

        return view('leaderboard.index', [
            'profiles' => $profiles,
            'rankOffset' => $rankOffset,
            'currentUserId' => auth()->id(),
            'currentUserGlobalRank' => $currentProfile !== null
                ? $this->globalRankFor($currentProfile)
                : null,
        ]);
    }

    private function globalRankFor(PlayerProfile $profile): int
    {
        $adminUserIds = User::query()->where('is_admin', true)->pluck('id');

        $betterRankedCount = PlayerProfile::query()
            ->whereNotIn('user_id', $adminUserIds)
            ->where(function ($query) use ($profile) {
                $query->where('reputation', '>', $profile->reputation)
                    ->orWhere(function ($query) use ($profile) {
                        $query->where('reputation', $profile->reputation)
                            ->where('level', '>', $profile->level);
                    })
                    ->orWhere(function ($query) use ($profile) {
                        $query->where('reputation', $profile->reputation)
                            ->where('level', $profile->level)
                            ->where('user_id', '<', $profile->user_id);
                    });
            })
            ->count();

        return $betterRankedCount + 1;
    }
}
