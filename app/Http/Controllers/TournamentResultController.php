<?php

namespace App\Http\Controllers;

use App\Models\ClubTournamentEntry;
use App\Models\RaceResult;
use Illuminate\View\View;

class TournamentResultController extends Controller
{
    public function show(RaceResult $raceResult): View
    {
        if ($raceResult->user_id !== auth()->id()) {
            abort(403);
        }

        $entry = ClubTournamentEntry::query()
            ->where('race_result_id', $raceResult->id)
            ->first();

        return view('tournament-results.show', [
            'raceResult' => $raceResult,
            'entry' => $entry,
        ]);
    }
}
