<?php

namespace App\Http\Controllers;

use App\Services\DailyRewardService;
use App\Services\PremiumFuelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PremiumFuelController extends Controller
{
    public function __construct(
        private readonly DailyRewardService $dailyRewardService,
        private readonly PremiumFuelService $premiumFuelService,
    ) {}

    public function index(): View
    {
        $user = auth()->user();
        $profile = $user->playerProfile;
        $storageMax = $profile !== null ? $this->premiumFuelService->storageMax($profile) : 0;

        $claimedToday = $this->dailyRewardService->hasClaimedPremiumToday($user);
        $atCap = $profile !== null
            && ! $claimedToday
            && $this->premiumFuelService->isAtCap($profile);

        return view('premium-fuel.index', [
            'profile' => $profile,
            'storageMax' => $storageMax,
            'claimedToday' => $claimedToday,
            'atCap' => $atCap,
            'canClaim' => $this->dailyRewardService->canClaimPremiumToday($user),
            'configuredAmount' => (int) config('game.premium_fuel.daily_claim_amount', 1),
            'timezone' => config('app.timezone'),
        ]);
    }

    public function store(): RedirectResponse
    {
        try {
            $result = $this->dailyRewardService->claimPremium(auth()->user());
        } catch (ValidationException $exception) {
            return redirect()
                ->route('premium-fuel.index')
                ->withErrors($exception->errors());
        }

        return redirect()
            ->route('premium-fuel.index')
            ->with(
                'status',
                $result->replayed ? 'premium-fuel-existing' : 'premium-fuel-claimed',
            )
            ->with('premium_fuel_granted', $result->premiumFuelGranted);
    }
}
