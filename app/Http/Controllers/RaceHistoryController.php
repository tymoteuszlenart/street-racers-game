<?php

namespace App\Http\Controllers;

use App\Models\RaceResult;
use Illuminate\View\View;

class RaceHistoryController extends Controller
{
    public function index(): View
    {
        $results = RaceResult::query()
            ->where('user_id', auth()->id())
            ->with(['race', 'pvpRace.defender'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('race-history.index', [
            'results' => $results,
        ]);
    }
}
