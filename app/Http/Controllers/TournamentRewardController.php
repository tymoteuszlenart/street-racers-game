<?php

namespace App\Http\Controllers;

use App\Enums\ClubTournamentStatus;
use App\Models\ClubTournament;
use App\Models\ClubTournamentRewardGrant;
use Illuminate\View\View;

class TournamentRewardController extends Controller
{
    public function index(): View
    {
        $grants = ClubTournamentRewardGrant::query()
            ->where('user_id', auth()->id())
            ->with('tournament')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $lastClosedSeason = ClubTournament::query()
            ->where('status', ClubTournamentStatus::RewardsDistributed)
            ->orderByDesc('ends_at')
            ->first();

        return view('tournament-rewards.index', [
            'grants' => $grants,
            'lastClosedSeason' => $lastClosedSeason,
        ]);
    }
}
