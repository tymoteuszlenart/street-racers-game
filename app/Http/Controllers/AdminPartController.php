<?php

namespace App\Http\Controllers;

use App\Models\Part;
use Illuminate\View\View;

class AdminPartController extends Controller
{
    public function index(): View
    {
        $parts = Part::query()
            ->with(['user', 'partModel', 'car.carModel'])
            ->orderByDesc('id')
            ->paginate(50);

        return view('admin.parts.index', [
            'parts' => $parts,
        ]);
    }
}
