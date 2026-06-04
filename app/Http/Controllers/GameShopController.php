<?php

namespace App\Http\Controllers;

use App\Enums\PartSlot;
use App\Http\Requests\PurchaseCarRequest;
use App\Http\Requests\PurchasePartRequest;
use App\Models\CarModel;
use App\Models\PartModel;
use App\Services\DealerService;
use App\Services\TuningShopService;
use App\Support\PartsShopUnlock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GameShopController extends Controller
{
    public function __construct(
        private readonly DealerService $dealerService,
        private readonly TuningShopService $tuningShopService,
    ) {}

    public function index(Request $request): View
    {
        $profile = $request->user()->playerProfile;
        $level = $profile?->level ?? 1;
        $partsUnlocked = $level >= PartsShopUnlock::shopLevel();

        $carModels = CarModel::query()
            ->active()
            ->dealerCatalog()
            ->unlockedForLevel($level)
            ->orderBy('unlock_level')
            ->orderBy('price')
            ->get();

        $partModels = $partsUnlocked
            ? PartModel::query()
                ->shopCatalog($level)
                ->get()
                ->filter(fn (PartModel $partModel) => PartsShopUnlock::slotUnlocked($partModel->slot, $level))
            : collect();

        $partSlots = PartSlot::cases();
        $partModelsBySlot = $partModels->groupBy(fn (PartModel $partModel) => $partModel->slot->value);
        $slotUnlockLevels = collect($partSlots)
            ->mapWithKeys(fn (PartSlot $slot) => [$slot->value => PartsShopUnlock::slotLevel($slot)]);

        $validTabs = array_merge(['cars'], PartSlot::values());
        $requestedTab = $request->string('tab')->toString();
        $initialTab = in_array($requestedTab, $validTabs, true)
            ? $requestedTab
            : 'cars';
        if ($requestedTab === 'parts') {
            $initialTab = $partModelsBySlot->keys()->first() ?? PartSlot::Engine->value;
        }

        return view('game-shop.index', [
            'carModels' => $carModels,
            'partModelsBySlot' => $partModelsBySlot,
            'partSlots' => $partSlots,
            'cash' => $profile?->cash ?? 0,
            'playerLevel' => $level,
            'partsUnlocked' => $partsUnlocked,
            'partsUnlockLevel' => PartsShopUnlock::shopLevel(),
            'slotUnlockLevels' => $slotUnlockLevels,
            'initialTab' => $initialTab,
        ]);
    }

    public function purchaseCar(PurchaseCarRequest $request, CarModel $carModel): RedirectResponse
    {
        $car = $this->dealerService->purchase(
            $request->user(),
            $carModel,
        );

        return redirect()
            ->route('garage.show', $car)
            ->with('status', 'car-purchased');
    }

    public function purchasePart(PurchasePartRequest $request, PartModel $partModel): RedirectResponse
    {
        $part = $this->tuningShopService->purchase($request->user(), $partModel);

        return redirect()
            ->route('shop.index', ['tab' => $partModel->slot->value])
            ->with('status', 'part-purchased')
            ->with('purchased_part_id', $part->id);
    }
}
