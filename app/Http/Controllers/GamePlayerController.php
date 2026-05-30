<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PlayerLevelService;
use Illuminate\View\View;

class GamePlayerController extends Controller
{
    public function show(User $user, PlayerLevelService $playerLevelService): View
    {
        $user->load('clubMember.club');

        $profile = $user->playerProfile()
            ->with('activeCar.carModel')
            ->firstOrFail();

        $levelProgress = $user->id === auth()->id()
            ? $playerLevelService->progressTowardNextLevel($profile)
            : null;

        return view('players.show', [
            'player' => $user,
            'profile' => $profile,
            'levelProgress' => $levelProgress,
            'isSelf' => $user->id === auth()->id(),
            'driverStatLabels' => config('game.player.driver_stats.labels', []),
        ]);
    }
}
