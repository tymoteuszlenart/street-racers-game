<?php

namespace App\Http\Controllers;

use App\Services\DailyRewardService;
use App\Services\FuelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DailyRewardController extends Controller
{
    public function __construct(
        private readonly DailyRewardService $dailyRewardService,
        private readonly FuelService $fuelService,
    ) {}

    public function index(): View
    {
        $user = auth()->user();
        $profile = $user->playerProfile;

        if ($profile !== null) {
            $this->fuelService->regenerate($profile);
            $profile->refresh();
        }

        $claimedToday = $this->dailyRewardService->hasClaimedLoginToday($user);
        $tankFull = $profile !== null
            && ! $claimedToday
            && $this->fuelService->isTankFull($profile);

        return view('daily-rewards.index', [
            'profile' => $profile,
            'claimedToday' => $claimedToday,
            'tankFull' => $tankFull,
            'canClaim' => ! $claimedToday && ! $tankFull,
            'configuredFuel' => (int) config('game.daily_rewards.login.fuel', 20),
            'timezone' => config('app.timezone'),
        ]);
    }

    public function store(): RedirectResponse
    {
        try {
            $result = $this->dailyRewardService->claimLogin(auth()->user());
        } catch (ValidationException $exception) {
            return redirect()
                ->route('daily-rewards.index')
                ->withErrors($exception->errors());
        }

        return redirect()
            ->route('daily-rewards.index')
            ->with(
                'status',
                $result->replayed ? 'daily-reward-existing' : 'daily-reward-claimed',
            )
            ->with('fuel_granted', $result->fuelGranted);
    }
}
