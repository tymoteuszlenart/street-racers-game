<?php

namespace App\Http\Controllers;

use App\Models\Club;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClubRankingController extends Controller
{
    public function index(Request $request): View
    {
        $clubs = Club::query()
            ->withCount('members')
            ->orderByDesc('points')
            ->orderBy('id')
            ->paginate(50);

        $rankOffset = ($clubs->currentPage() - 1) * $clubs->perPage();
        $userClubId = $request->user()->clubMember?->club_id;

        return view('clubs.rankings', [
            'clubs' => $clubs,
            'rankOffset' => $rankOffset,
            'userClubId' => $userClubId,
        ]);
    }
}
