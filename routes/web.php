<?php

use App\Http\Controllers\ActiveCarController;
use App\Http\Controllers\AdminPurchaseController;
use App\Http\Controllers\CarSellController;
use App\Http\Controllers\CarUpgradeController;
use App\Http\Controllers\ClubController;
use App\Http\Controllers\ClubRankingController;
use App\Http\Controllers\ClubTournamentController;
use App\Http\Controllers\DailyRewardController;
use App\Http\Controllers\DriverStatController;
use App\Http\Controllers\GamePlayerController;
use App\Http\Controllers\GameShopController;
use App\Http\Controllers\GarageController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\MechanicController;
use App\Http\Controllers\OpenCupController;
use App\Http\Controllers\PartSellController;
use App\Http\Controllers\PremiumController;
use App\Http\Controllers\PremiumFuelController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseHistoryController;
use App\Http\Controllers\PvpRaceController;
use App\Http\Controllers\RaceController;
use App\Http\Controllers\RaceHistoryController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\TournamentResultController;
use App\Http\Controllers\TournamentRewardController;
use App\Services\DailyRewardService;
use App\Services\PlayerLevelService;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/stripe', StripeWebhookController::class)->name('webhooks.stripe');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    $user = auth()->user()?->load('clubMember');
    $profile = $user?->playerProfile?->load('activeCar.carModel');
    $dailyRewardService = app(DailyRewardService::class);
    $dailyRewardAvailable = $user !== null && $dailyRewardService->canClaimLoginToday($user);
    $dailyRewardTankFull = $user !== null && $dailyRewardService->isLoginClaimBlockedByFullTank($user);
    $premiumFuelAvailable = $user !== null && $dailyRewardService->canClaimPremiumToday($user);
    $premiumFuelAtCap = $user !== null && $dailyRewardService->isPremiumClaimBlockedByCap($user);
    $tournamentsUnlockLevel = config('game.tournaments.unlock_level');
    $tournamentsUnlocked = ($profile?->level ?? 1) >= $tournamentsUnlockLevel;
    $clubsUnlockLevel = config('game.clubs.unlock_level');
    $clubsUnlocked = ($profile?->level ?? 1) >= $clubsUnlockLevel;
    $levelProgress = $profile !== null
        ? app(PlayerLevelService::class)->progressTowardNextLevel($profile)
        : null;

    return view('dashboard', [
        'profile' => $profile,
        'dailyRewardAvailable' => $dailyRewardAvailable,
        'dailyRewardTankFull' => $dailyRewardTankFull,
        'premiumFuelAvailable' => $premiumFuelAvailable,
        'premiumFuelAtCap' => $premiumFuelAtCap,
        'tournamentsUnlocked' => $tournamentsUnlocked,
        'tournamentsUnlockLevel' => $tournamentsUnlockLevel,
        'clubsUnlocked' => $clubsUnlocked,
        'clubsUnlockLevel' => $clubsUnlockLevel,
        'userInClub' => $user?->clubMember !== null,
        'levelProgress' => $levelProgress,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/garage', [GarageController::class, 'index'])->name('garage.index');
    Route::delete('/garage/parts/{part}', [PartSellController::class, 'destroy'])->name('garage.parts.sell');
    Route::get('/garage/{car}', [GarageController::class, 'show'])->name('garage.show');
    Route::delete('/garage/{car}', [CarSellController::class, 'destroy'])->name('garage.cars.sell');
    Route::patch('/garage/{car}/active', [ActiveCarController::class, 'update'])->name('garage.active');

    Route::get('/shop', [GameShopController::class, 'index'])->name('shop.index');
    Route::post('/shop/cars/{carModel}', [GameShopController::class, 'purchaseCar'])->name('shop.cars.purchase');
    Route::middleware('parts_shop.unlocked')->post('/shop/parts/{partModel}', [GameShopController::class, 'purchasePart'])->name('shop.parts.purchase');

    Route::redirect('/dealer', '/shop')->name('dealer.index');
    Route::post('/dealer/{carModel}', [GameShopController::class, 'purchaseCar'])->name('dealer.purchase');
    Route::redirect('/tuning', '/shop?tab=parts')->name('tuning.index');
    Route::middleware('parts_shop.unlocked')->post('/tuning/{partModel}', [GameShopController::class, 'purchasePart'])->name('tuning.purchase');

    Route::middleware('parts_shop.unlocked')->group(function () {
        Route::get('/garage/{car}/upgrades', [CarUpgradeController::class, 'show'])->name('garage.upgrades');
        Route::post('/garage/{car}/upgrades/{part}', [CarUpgradeController::class, 'equip'])->name('garage.upgrades.equip');
        Route::delete('/garage/{car}/upgrades/{part}', [CarUpgradeController::class, 'unequip'])->name('garage.upgrades.unequip');
    });

    Route::middleware('mechanic.unlocked')->group(function () {
        Route::get('/mechanic', [MechanicController::class, 'index'])->name('mechanic.index');
        Route::post('/mechanic/parts/{part}/upgrade', [MechanicController::class, 'upgradePart'])->name('mechanic.parts.upgrade');
        Route::post('/mechanic/cars/{car}/repair', [MechanicController::class, 'repairCar'])->name('mechanic.cars.repair');
        Route::post('/mechanic/parts/{part}/repair', [MechanicController::class, 'repairPart'])->name('mechanic.parts.repair');
    });

    Route::get('/races', [RaceController::class, 'index'])->name('races.index');
    Route::post('/races/{race}', [RaceController::class, 'store'])->name('races.start');
    Route::get('/races/results/{raceResult}', [RaceController::class, 'show'])->name('races.show');

    Route::middleware('open_cup.unlocked')->group(function () {
        Route::get('/cups', [OpenCupController::class, 'index'])->name('cups.index');
        Route::post('/cups', [OpenCupController::class, 'store'])->name('cups.store');
        Route::get('/cups/{cup}', [OpenCupController::class, 'show'])->name('cups.show');
        Route::post('/cups/{cup}/join', [OpenCupController::class, 'join'])->name('cups.join');
    });

    Route::get('/pvp', [PvpRaceController::class, 'index'])->name('pvp.index');
    Route::post('/pvp/{defender}', [PvpRaceController::class, 'store'])->name('pvp.start');
    Route::get('/pvp/results/{raceResult}', [PvpRaceController::class, 'show'])->name('pvp.show');
    Route::get('/pvp/history', [PvpRaceController::class, 'history'])->name('pvp.history');

    Route::get('/daily-rewards', [DailyRewardController::class, 'index'])->name('daily-rewards.index');
    Route::post('/daily-rewards/login', [DailyRewardController::class, 'store'])->name('daily-rewards.claim');

    Route::get('/premium', [PremiumController::class, 'index'])->name('premium.index');
    Route::post('/premium/checkout/{shopProduct:slug}', [PremiumController::class, 'checkout'])->name('premium.checkout');
    Route::get('/premium/success', [PremiumController::class, 'success'])->name('premium.success');
    Route::get('/premium/cancel', [PremiumController::class, 'cancel'])->name('premium.cancel');

    Route::redirect('/shop/success', '/premium/success');
    Route::redirect('/shop/cancel', '/premium/cancel');

    Route::get('/purchases', [PurchaseHistoryController::class, 'index'])->name('purchases.index');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/purchases', [AdminPurchaseController::class, 'index'])->name('purchases.index');
        Route::get('/purchases/{paymentOrder}', [AdminPurchaseController::class, 'show'])->name('purchases.show');
    });

    Route::middleware('tournaments.unlocked')->group(function () {
        Route::get('/premium-fuel', [PremiumFuelController::class, 'index'])->name('premium-fuel.index');
        Route::post('/premium-fuel/claim', [PremiumFuelController::class, 'store'])->name('premium-fuel.claim');
        Route::get('/tournament-rewards', [TournamentRewardController::class, 'index'])->name('tournament-rewards.index');
        Route::get('/tournament-results/{raceResult}', [TournamentResultController::class, 'show'])->name('tournament-results.show');
    });

    Route::get('/race-history', [RaceHistoryController::class, 'index'])->name('race-history.index');

    Route::get('/rankings', [LeaderboardController::class, 'index'])->name('leaderboard.index');
    Route::get('/players/{user}', [GamePlayerController::class, 'show'])->name('players.show');
    Route::post('/players/stats', [DriverStatController::class, 'store'])->name('players.stats.store');

    Route::middleware('clubs.unlocked')->group(function () {
        Route::get('/clubs', [ClubController::class, 'index'])->name('clubs.index');
        Route::get('/clubs/create', [ClubController::class, 'create'])->name('clubs.create');
        Route::post('/clubs', [ClubController::class, 'store'])->name('clubs.store');
        Route::get('/clubs/rankings', [ClubRankingController::class, 'index'])->name('clubs.rankings');
        Route::middleware('tournaments.unlocked')->group(function () {
            Route::get('/clubs/{club:slug}/tournament', [ClubTournamentController::class, 'show'])->name('clubs.tournament');
            Route::post('/clubs/{club:slug}/tournament/races', [ClubTournamentController::class, 'store'])
                ->name('clubs.tournament.races.store');
        });
        Route::get('/clubs/{club:slug}', [ClubController::class, 'show'])->name('clubs.show');
        Route::post('/clubs/{club:slug}/join', [ClubController::class, 'join'])->name('clubs.join');
        Route::post('/clubs/{club:slug}/leave', [ClubController::class, 'leave'])->name('clubs.leave');
        Route::delete('/clubs/{club:slug}/members/{member}', [ClubController::class, 'kick'])->name('clubs.members.kick');
        Route::patch('/clubs/{club:slug}/members/{member}/role', [ClubController::class, 'updateMemberRole'])->name('clubs.members.role');
        Route::post('/clubs/{club:slug}/transfer-ownership', [ClubController::class, 'transferOwnership'])->name('clubs.transfer-ownership');
        Route::delete('/clubs/{club:slug}', [ClubController::class, 'destroy'])->name('clubs.destroy');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
