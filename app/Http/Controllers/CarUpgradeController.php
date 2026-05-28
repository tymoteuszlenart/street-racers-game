<?php

namespace App\Http\Controllers;

use App\Enums\PartSlot;
use App\Models\Car;
use App\Models\Part;
use App\Services\CarStatAggregator;
use App\Services\PartEquipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CarUpgradeController extends Controller
{
    public function __construct(
        private readonly PartEquipService $partEquipService,
        private readonly CarStatAggregator $carStatAggregator,
    ) {}

    public function show(Request $request, Car $car): View
    {
        $this->authorize('view', $car);

        $user = $request->user();
        $profile = $user->playerProfile;

        if (($profile?->level ?? 1) < 5) {
            abort(403, 'Reach level 5 to tune your cars.');
        }

        $car->load(['carModel', 'parts.partModel']);

        $equippedBySlot = $car->parts->keyBy(fn (Part $part) => $part->slot->value);

        $inventory = $user->parts()
            ->with('partModel')
            ->where(function ($query) use ($car) {
                $query->whereNull('car_id')
                    ->orWhere('car_id', '!=', $car->id);
            })
            ->orderBy('slot')
            ->get()
            ->filter(fn (Part $part) => $this->partFitsCar($part, $car));

        $effectiveStats = $this->carStatAggregator->aggregate($car);
        $baseStats = [
            'power' => $car->carModel->power,
            'acceleration' => $car->carModel->acceleration,
            'grip' => $car->carModel->grip,
            'handling' => $car->carModel->handling,
        ];

        return view('garage.upgrades', [
            'car' => $car,
            'slots' => PartSlot::cases(),
            'equippedBySlot' => $equippedBySlot,
            'inventory' => $inventory,
            'effectiveStats' => $effectiveStats,
            'baseStats' => $baseStats,
        ]);
    }

    public function equip(Request $request, Car $car, Part $part): RedirectResponse
    {
        $this->authorize('view', $car);
        $this->authorize('update', $part);

        $this->partEquipService->equip($request->user(), $part, $car);

        return redirect()
            ->route('garage.upgrades', $car)
            ->with('status', 'part-equipped');
    }

    public function unequip(Request $request, Car $car, Part $part): RedirectResponse
    {
        $this->authorize('view', $car);
        $this->authorize('update', $part);

        if ($part->car_id !== $car->id) {
            abort(404);
        }

        $this->partEquipService->unequip($request->user(), $part, $car);

        return redirect()
            ->route('garage.upgrades', $car)
            ->with('status', 'part-unequipped');
    }

    private function partFitsCar(Part $part, Car $car): bool
    {
        $carModel = $car->carModel;
        $partModel = $part->partModel;

        if (! in_array($part->slot->value, $carModel->resolvedUpgradeSlots(), true)) {
            return false;
        }

        return $carModel->class->rank() >= $partModel->min_car_class->rank();
    }
}
