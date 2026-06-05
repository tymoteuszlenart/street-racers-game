<?php

namespace App\Http\Controllers;

use App\Enums\CarClass;
use App\Enums\PartSlot;
use App\Models\Car;
use App\Models\Part;
use App\Services\CarStatAggregator;
use App\Services\SellPriceCalculator;
use App\Support\PartsShopUnlock;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class GarageController extends Controller
{
    public function __construct(
        private readonly CarStatAggregator $carStatAggregator,
        private readonly SellPriceCalculator $sellPriceCalculator,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $profile = $user->playerProfile;

        $cars = $user->cars()
            ->with('carModel')
            ->latest()
            ->get();

        $parts = $user->parts()
            ->with(['partModel', 'car.carModel'])
            ->orderBy('slot')
            ->latest()
            ->get();

        /** @var Collection<string, Collection<int, Car>> $carsByClass */
        $carsByClass = $cars->groupBy(fn (Car $car) => $car->carModel->class->value);

        /** @var Collection<string, Collection<int, Part>> $partsBySlot */
        $partsBySlot = $parts->groupBy(fn (Part $part) => $part->slot->value);

        $inventoryParts = $parts->filter(fn (Part $part) => $part->car_id === null)->values();

        /** @var Collection<string, Collection<int, Part>> $inventoryPartsBySlot */
        $inventoryPartsBySlot = $inventoryParts->groupBy(fn (Part $part) => $part->slot->value);

        $partSellQuotes = $inventoryParts->mapWithKeys(fn (Part $part) => [
            $part->id => $this->sellPriceCalculator->quotePart($part),
        ]);

        return view('garage.index', [
            'cars' => $cars,
            'carsByClass' => $carsByClass,
            'carClasses' => CarClass::cases(),
            'parts' => $parts,
            'partsBySlot' => $partsBySlot,
            'inventoryParts' => $inventoryParts,
            'inventoryPartsBySlot' => $inventoryPartsBySlot,
            'partSlots' => PartSlot::cases(),
            'activeCarId' => $profile?->active_car_id,
            'partSellQuotes' => $partSellQuotes,
            'playerLevel' => $profile?->level ?? 1,
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

        $sellQuote = $this->sellPriceCalculator->quoteCar($request->user(), $car, includeEquippedParts: true);

        return view('garage.show', [
            'car' => $car,
            'sellQuote' => $sellQuote,
            'isActive' => $profile?->active_car_id === $car->id,
            'playerLevel' => $playerLevel,
            'partsEquipUnlocked' => $playerLevel >= PartsShopUnlock::shopLevel(),
            'mechanicUnlocked' => $playerLevel >= (int) config('game.mechanic.unlock_level', 10),
            'baseStats' => $baseStats,
            'effectiveStats' => $this->carStatAggregator->aggregate($car),
        ]);
    }
}
