<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Services\CarSellService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CarSellController extends Controller
{
    public function __construct(
        private readonly CarSellService $carSellService,
    ) {}

    public function destroy(Request $request, Car $car): RedirectResponse
    {
        $this->authorize('delete', $car);

        $quote = $this->carSellService->sell($request->user(), $car);

        $partCount = count(array_filter(
            $quote->lines,
            fn ($line) => $line->kind === 'part',
        ));

        return redirect()
            ->route('garage.index')
            ->with('status', 'car-sold')
            ->with('sold_amount', $quote->total)
            ->with('sold_part_count', $partCount);
    }
}
