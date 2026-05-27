<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseCarRequest;
use App\Models\CarModel;
use App\Services\DealerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DealerController extends Controller
{
    public function __construct(
        private readonly DealerService $dealerService,
    ) {}

    public function index(Request $request): View
    {
        $profile = $request->user()->playerProfile;
        $level = $profile?->level ?? 1;

        $carModels = CarModel::query()
            ->active()
            ->unlockedForLevel($level)
            ->orderBy('unlock_level')
            ->orderBy('price')
            ->get();

        return view('dealer.index', [
            'carModels' => $carModels,
            'cash' => $profile?->cash ?? 0,
        ]);
    }

    public function store(PurchaseCarRequest $request, CarModel $carModel): RedirectResponse
    {
        $car = $this->dealerService->purchase(
            $request->user(),
            $carModel,
            $request->validated('nickname'),
        );

        return redirect()
            ->route('garage.show', $car)
            ->with('status', 'car-purchased');
    }
}
