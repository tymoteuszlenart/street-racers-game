<?php

namespace App\Providers;

use App\Services\PlayerLevelService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', function ($view): void {
            $user = auth()->user();

            if ($user === null) {
                $view->with('playerHud', null);

                return;
            }

            $profile = $user->playerProfile;

            if ($profile === null) {
                $view->with('playerHud', null);

                return;
            }

            $levelService = app(PlayerLevelService::class);
            $progress = $levelService->progressTowardNextLevel($profile);

            $view->with('playerHud', [
                'nickname' => $user->name,
                'cash' => $profile->cash,
                'cups' => $profile->cups,
                'fuelCurrent' => $profile->fuel_current,
                'fuelMax' => $profile->fuel_max,
                'premiumFuelCurrent' => $profile->premium_fuel_current,
                'premiumFuelMax' => min(
                    $profile->premium_fuel_max,
                    (int) config('game.premium_fuel.default_max', 5),
                ),
                'level' => $profile->level,
                'progress' => $progress,
                'percent' => $progress !== null
                    ? ($progress['current'] / max(1, $progress['required'])) * 100
                    : 100,
                'maxLevel' => $levelService->maxLevel(),
            ]);
        });
    }
}
