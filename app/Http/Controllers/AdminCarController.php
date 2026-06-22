<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\View\View;

class AdminCarController extends Controller
{
    public function index(): View
    {
        $cars = Car::query()
            ->with(['user', 'carModel'])
            ->orderByDesc('id')
            ->paginate(50);

        return view('admin.cars.index', [
            'cars' => $cars,
        ]);
    }
}
