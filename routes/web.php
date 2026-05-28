<?php

use App\Http\Controllers\ActiveCarController;
use App\Http\Controllers\DealerController;
use App\Http\Controllers\GarageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RaceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    $user = auth()->user();
    $profile = $user?->playerProfile?->load('activeCar.carModel');

    return view('dashboard', [
        'profile' => $profile,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/garage', [GarageController::class, 'index'])->name('garage.index');
    Route::get('/garage/{car}', [GarageController::class, 'show'])->name('garage.show');
    Route::patch('/garage/{car}/active', [ActiveCarController::class, 'update'])->name('garage.active');

    Route::get('/dealer', [DealerController::class, 'index'])->name('dealer.index');
    Route::post('/dealer/{carModel}', [DealerController::class, 'store'])->name('dealer.purchase');

    Route::get('/races', [RaceController::class, 'index'])->name('races.index');
    Route::post('/races/{race}', [RaceController::class, 'store'])
        ->middleware('throttle.race-start')
        ->name('races.start');
    Route::get('/races/results/{raceResult}', [RaceController::class, 'show'])->name('races.show');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
