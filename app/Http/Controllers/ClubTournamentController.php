<?php

namespace App\Http\Controllers;

use App\Exceptions\IdempotencyKeyConflictException;
use App\Exceptions\IdempotencyKeyExpiredException;
use App\Exceptions\RaceAttemptFailedException;
use App\Exceptions\RaceAttemptPendingException;
use App\Http\Requests\StartClubTournamentRaceRequest;
use App\Models\Club;
use App\Models\ClubTournamentEntry;
use App\Services\ClubTournamentRaceService;
use App\Services\ClubTournamentSeasonService;
use App\Services\PremiumFuelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ClubTournamentController extends Controller
{
    public function __construct(
        private readonly ClubTournamentSeasonService $seasonService,
        private readonly ClubTournamentRaceService $raceService,
        private readonly PremiumFuelService $premiumFuelService,
    ) {}

    public function show(Club $club): View
    {
        $this->authorize('view', $club);

        $user = auth()->user()->load('clubMember', 'playerProfile');
        $profile = $user->playerProfile;
        $tournament = $this->seasonService->active();

        $isMember = $user->clubMember?->club_id === $club->id;
        $attemptCount = 0;
        $countedEntries = collect();

        if ($isMember) {
            $attemptCount = ClubTournamentEntry::query()
                ->where('club_tournament_id', $tournament->id)
                ->where('user_id', $user->id)
                ->count();

            $countedEntries = ClubTournamentEntry::query()
                ->where('club_tournament_id', $tournament->id)
                ->where('user_id', $user->id)
                ->where('counts_toward_club', true)
                ->orderByDesc('points')
                ->limit((int) config('game.tournaments.counted_attempts_per_player', 10))
                ->get();
        }

        $storageMax = $profile !== null ? $this->premiumFuelService->storageMax($profile) : 0;

        return view('clubs.tournament', [
            'club' => $club,
            'tournament' => $tournament,
            'profile' => $profile,
            'isMember' => $isMember,
            'attemptCount' => $attemptCount,
            'maxAttempts' => (int) config('game.tournaments.max_attempts_per_player', 20),
            'countedEntries' => $countedEntries,
            'storageMax' => $storageMax,
            'idempotencyKey' => (string) Str::uuid(),
        ]);
    }

    public function store(
        StartClubTournamentRaceRequest $request,
        Club $club,
    ): RedirectResponse|JsonResponse {
        $this->authorize('view', $club);

        try {
            $result = $this->raceService->start(
                $request->user(),
                $club,
                $request->idempotencyKey(),
            );
        } catch (RaceAttemptPendingException|IdempotencyKeyConflictException|IdempotencyKeyExpiredException|RaceAttemptFailedException $exception) {
            return $this->raceStartConflictResponse($request, $exception->getMessage());
        } catch (ValidationException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $exception->errors(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return back()->withErrors($exception->errors())->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'race_result_id' => $result->raceResult->id,
                'replayed' => $result->replayed,
                'points' => $result->entry->points,
            ]);
        }

        return redirect()
            ->route('tournament-results.show', $result->raceResult)
            ->with('status', $result->replayed ? 'race-existing-result' : 'race-complete');
    }

    private function raceStartConflictResponse(
        StartClubTournamentRaceRequest $request,
        string $message,
    ): RedirectResponse|JsonResponse {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], Response::HTTP_CONFLICT);
        }

        return back()->withErrors(['race' => $message])->withInput();
    }
}
