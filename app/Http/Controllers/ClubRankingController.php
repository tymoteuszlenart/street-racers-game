<?php

namespace App\Http\Controllers;

use App\Models\Club;
use App\Services\ClubTournamentSeasonRankingService;
use App\Services\ClubTournamentSeasonService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class ClubRankingController extends Controller
{
    public function __construct(
        private readonly ClubTournamentSeasonService $seasonService,
        private readonly ClubTournamentSeasonRankingService $rankingService,
    ) {}

    public function index(Request $request): View
    {
        $tournament = $this->seasonService->active();
        $perPage = 50;
        $page = max(1, (int) $request->query('page', 1));

        $ranked = $this->rankingService->rankedClubsForSeason($tournament);
        $pageIds = $ranked->forPage($page, $perPage)->pluck('id');

        $clubsOnPage = Club::query()
            ->whereIn('id', $pageIds)
            ->withCount('members')
            ->get()
            ->sortBy(fn (Club $club): int => $pageIds->search($club->id))
            ->values();

        $clubs = new LengthAwarePaginator(
            $clubsOnPage,
            $ranked->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ],
        );

        $rankOffset = ($clubs->currentPage() - 1) * $clubs->perPage();
        $userClubId = $request->user()->clubMember?->club_id;

        return view('clubs.rankings', [
            'clubs' => $clubs,
            'rankOffset' => $rankOffset,
            'userClubId' => $userClubId,
        ]);
    }
}
