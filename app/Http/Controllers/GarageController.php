<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GarageController extends Controller
{
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

        $car->load('carModel');

        $profile = $request->user()->playerProfile;

        return view('garage.show', [
            'car' => $car,
            'isActive' => $profile?->active_car_id === $car->id,
        ]);
    }
}
