<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Part;
use App\Services\MechanicService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MechanicController extends Controller
{
    public function __construct(
        private readonly MechanicService $mechanicService,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $profile = $user->playerProfile;

        $cars = $user->cars()
            ->with('carModel')
            ->orderBy('id')
            ->get();

        $parts = $user->parts()
            ->with(['partModel', 'car.carModel'])
            ->orderBy('slot')
            ->orderBy('id')
            ->get();

        $carRepairCosts = $cars->mapWithKeys(fn (Car $car) => [
            $car->id => $this->mechanicService->repairCarCost($car),
        ]);

        $partRepairCosts = $parts->mapWithKeys(fn (Part $part) => [
            $part->id => $this->mechanicService->repairPartCost($part),
        ]);

        $upgradeCosts = $parts->mapWithKeys(fn (Part $part) => [
            $part->id => $this->mechanicService->upgradeCost($part),
        ]);

        return view('mechanic.index', [
            'cash' => $profile?->cash ?? 0,
            'cars' => $cars,
            'parts' => $parts,
            'carRepairCosts' => $carRepairCosts,
            'partRepairCosts' => $partRepairCosts,
            'upgradeCosts' => $upgradeCosts,
            'maxUpgradeLevel' => (int) config('game.mechanic.max_upgrade_level', 9),
        ]);
    }

    public function upgradePart(Request $request, Part $part): RedirectResponse
    {
        $this->authorize('update', $part);

        $this->mechanicService->upgradePart($request->user(), $part);

        return redirect()
            ->route('mechanic.index', ['tab' => 'upgrade'])
            ->with('status', 'part-upgraded');
    }

    public function repairCar(Request $request, Car $car): RedirectResponse
    {
        abort_unless($car->user_id === $request->user()->id, 404);

        $this->mechanicService->repairCar($request->user(), $car);

        return redirect()
            ->route('mechanic.index', ['tab' => 'repair'])
            ->with('status', 'car-repaired');
    }

    public function repairPart(Request $request, Part $part): RedirectResponse
    {
        $this->authorize('update', $part);

        $this->mechanicService->repairPart($request->user(), $part);

        return redirect()
            ->route('mechanic.index', ['tab' => 'repair'])
            ->with('status', 'part-repaired');
    }
}
