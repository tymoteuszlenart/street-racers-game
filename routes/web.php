<?php

use App\Http\Controllers\ActiveCarController;
use App\Http\Controllers\CarUpgradeController;
use App\Http\Controllers\DailyRewardController;
use App\Http\Controllers\DealerController;
use App\Http\Controllers\GarageController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PvpRaceController;
use App\Http\Controllers\RaceController;
use App\Http\Controllers\RaceHistoryController;
use App\Http\Controllers\TuningShopController;
use App\Services\DailyRewardService;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    $user = auth()->user();
    $profile = $user?->playerProfile?->load('activeCar.carModel');
    $dailyRewardService = app(DailyRewardService::class);
    $dailyRewardAvailable = $user !== null && $dailyRewardService->canClaimLoginToday($user);
    $dailyRewardTankFull = $user !== null && $dailyRewardService->isLoginClaimBlockedByFullTank($user);

    return view('dashboard', [
        'profile' => $profile,
        'dailyRewardAvailable' => $dailyRewardAvailable,
        'dailyRewardTankFull' => $dailyRewardTankFull,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/garage', [GarageController::class, 'index'])->name('garage.index');
    Route::get('/garage/{car}', [GarageController::class, 'show'])->name('garage.show');
    Route::patch('/garage/{car}/active', [ActiveCarController::class, 'update'])->name('garage.active');

    Route::get('/dealer', [DealerController::class, 'index'])->name('dealer.index');
    Route::post('/dealer/{carModel}', [DealerController::class, 'store'])->name('dealer.purchase');

    Route::middleware('tuning.unlocked')->group(function () {
        Route::get('/tuning', [TuningShopController::class, 'index'])->name('tuning.index');
        Route::post('/tuning/{partModel}', [TuningShopController::class, 'store'])->name('tuning.purchase');

        Route::get('/garage/{car}/upgrades', [CarUpgradeController::class, 'show'])->name('garage.upgrades');
        Route::post('/garage/{car}/upgrades/{part}', [CarUpgradeController::class, 'equip'])->name('garage.upgrades.equip');
        Route::delete('/garage/{car}/upgrades/{part}', [CarUpgradeController::class, 'unequip'])->name('garage.upgrades.unequip');
    });

    Route::get('/races', [RaceController::class, 'index'])->name('races.index');
    Route::post('/races/{race}', [RaceController::class, 'store'])->name('races.start');
    Route::get('/races/results/{raceResult}', [RaceController::class, 'show'])->name('races.show');

    Route::get('/pvp', [PvpRaceController::class, 'index'])->name('pvp.index');
    Route::post('/pvp/{defender}', [PvpRaceController::class, 'store'])->name('pvp.start');
    Route::get('/pvp/results/{raceResult}', [PvpRaceController::class, 'show'])->name('pvp.show');
    Route::get('/pvp/history', [PvpRaceController::class, 'history'])->name('pvp.history');

    Route::get('/daily-rewards', [DailyRewardController::class, 'index'])->name('daily-rewards.index');
    Route::post('/daily-rewards/login', [DailyRewardController::class, 'store'])->name('daily-rewards.claim');

    Route::get('/race-history', [RaceHistoryController::class, 'index'])->name('race-history.index');

    Route::get('/rankings', [LeaderboardController::class, 'index'])->name('leaderboard.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
