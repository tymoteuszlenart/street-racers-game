<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchasePartRequest;
use App\Models\PartModel;
use App\Services\TuningShopService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TuningShopController extends Controller
{
    public function __construct(
        private readonly TuningShopService $tuningShopService,
    ) {}

    public function index(Request $request): View
    {
        $profile = $request->user()->playerProfile;
        $level = $profile?->level ?? 1;

        $partModels = PartModel::query()
            ->shopCatalog($level)
            ->get();

        return view('tuning.index', [
            'partModels' => $partModels,
            'cash' => $profile?->cash ?? 0,
            'playerLevel' => $level,
        ]);
    }

    public function store(PurchasePartRequest $request, PartModel $partModel): RedirectResponse
    {
        $part = $this->tuningShopService->purchase($request->user(), $partModel);

        return redirect()
            ->route('tuning.index')
            ->with('status', 'part-purchased')
            ->with('purchased_part_id', $part->id);
    }
}
