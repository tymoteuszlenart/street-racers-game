<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Services\ActiveCarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActiveCarController extends Controller
{
    public function __construct(
        private readonly ActiveCarService $activeCarService,
    ) {}

    public function update(Request $request, Car $car): RedirectResponse
    {
        abort_unless($car->user_id === $request->user()->id, 404);

        $this->activeCarService->setActive($request->user(), $car);

        return redirect()
            ->route('garage.show', $car)
            ->with('status', 'active-car-set');
    }
}
