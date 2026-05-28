<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Services\CarStatAggregator;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GarageController extends Controller
{
    public function __construct(
        private readonly CarStatAggregator $carStatAggregator,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $profile = $user->playerProfile;

        $cars = $user->cars()
            ->with('carModel')
            ->latest()
            ->get();

        return view('garage.index', [
            'cars' => $cars,
            'activeCarId' => $profile?->active_car_id,
        ]);
    }

    public function show(Request $request, Car $car): View
    {
        abort_unless($car->user_id === $request->user()->id, 404);

        $car->load(['carModel', 'parts.partModel']);

        $profile = $request->user()->playerProfile;
        $playerLevel = $profile?->level ?? 1;

        $baseStats = [
            'power' => $car->carModel->power,
            'acceleration' => $car->carModel->acceleration,
            'grip' => $car->carModel->grip,
            'handling' => $car->carModel->handling,
        ];

        return view('garage.show', [
            'car' => $car,
            'isActive' => $profile?->active_car_id === $car->id,
            'playerLevel' => $playerLevel,
            'tuningUnlocked' => $playerLevel >= 5,
            'baseStats' => $baseStats,
            'effectiveStats' => $this->carStatAggregator->aggregate($car),
        ]);
    }
}
