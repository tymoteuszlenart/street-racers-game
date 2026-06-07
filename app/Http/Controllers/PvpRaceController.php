<?php

namespace App\Http\Controllers;

use App\Enums\RaceAttemptType;
use App\Exceptions\IdempotencyKeyConflictException;
use App\Exceptions\IdempotencyKeyExpiredException;
use App\Exceptions\RaceAttemptFailedException;
use App\Exceptions\RaceAttemptPendingException;
use App\Exceptions\RaceStartRateLimitedException;
use App\Http\Requests\StartPvpRaceRequest;
use App\Models\PvpRace;
use App\Models\RaceResult;
use App\Models\User;
use App\Services\PvpRaceService;
use App\Support\PvpRaceResultRewards;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class PvpRaceController extends Controller
{
    public function __construct(
        private readonly PvpRaceService $pvpRaceService,
    ) {}

    public function index(): View
    {
        $user = auth()->user();
        $challengeUserId = request()->integer('challenge');

        $opponentsQuery = User::query()
            ->whereKeyNot($user->id)
            ->whereHas('playerProfile', fn ($query) => $query->whereNotNull('active_car_id'))
            ->with('playerProfile.activeCar.carModel');

        if ($challengeUserId > 0) {
            $opponentsQuery->orderByRaw('CASE WHEN users.id = ? THEN 0 ELSE 1 END', [$challengeUserId])
                ->orderBy('name');
        } else {
            $opponentsQuery->orderBy('name');
        }

        $opponents = $opponentsQuery->paginate(20);

        $opponentIdempotencyKeys = collect($opponents->items())->mapWithKeys(
            fn (User $opponent) => [$opponent->id => (string) Str::uuid()],
        );

        return view('pvp.index', [
            'profile' => $user->playerProfile->load('activeCar.carModel'),
            'opponents' => $opponents,
            'opponentIdempotencyKeys' => $opponentIdempotencyKeys,
            'challengeUserId' => $challengeUserId > 0 ? $challengeUserId : null,
        ]);
    }

    public function store(StartPvpRaceRequest $request, User $defender): RedirectResponse|JsonResponse
    {
        if ($defender->id === $request->user()->id) {
            return $this->pvpStartErrorResponse($request, 'You cannot race yourself.');
        }

        try {
            $result = $this->pvpRaceService->startPvpRace(
                $request->user(),
                $defender,
                $request->idempotencyKey(),
            );
        } catch (RaceAttemptPendingException|IdempotencyKeyConflictException|IdempotencyKeyExpiredException|RaceAttemptFailedException $exception) {
            return $this->pvpStartConflictResponse($request, $exception->getMessage(), $exception);
        } catch (RaceStartRateLimitedException $exception) {
            if ($request->expectsJson()) {
                return response()->json(
                    ['message' => $exception->getMessage()],
                    Response::HTTP_TOO_MANY_REQUESTS,
                    ['Retry-After' => (string) $exception->retryAfterSeconds],
                );
            }

            throw new TooManyRequestsHttpException(
                $exception->retryAfterSeconds,
                $exception->getMessage(),
                $exception,
            );
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
                'won' => $result->raceResult->won,
                'is_tie' => $result->raceResult->is_tie,
            ]);
        }

        return redirect()
            ->route('pvp.show', $result->raceResult)
            ->with('status', $result->replayed ? 'pvp-existing-result' : 'pvp-complete');
    }

    public function show(RaceResult $raceResult): View
    {
        if ($raceResult->user_id !== auth()->id() || $raceResult->attempt_type !== RaceAttemptType::Pvp) {
            abort(403);
        }

        return view('pvp.show', [
            'raceResult' => $raceResult->load('pvpRace.defender'),
            'rewards' => PvpRaceResultRewards::forResult($raceResult),
        ]);
    }

    public function history(): View
    {
        $races = PvpRace::query()
            ->where('defender_user_id', auth()->id())
            ->with(['challenger', 'raceResult'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('pvp.history', [
            'races' => $races,
        ]);
    }

    private function pvpStartConflictResponse(
        StartPvpRaceRequest $request,
        string $message,
        \Throwable $exception,
    ): RedirectResponse|JsonResponse {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], Response::HTTP_CONFLICT);
        }

        return $this->pvpStartErrorResponse($request, $message);
    }

    private function pvpStartErrorResponse(StartPvpRaceRequest $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'pvp' => [$message],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return back()
            ->withErrors(['pvp' => $message])
            ->withInput();
    }
}
