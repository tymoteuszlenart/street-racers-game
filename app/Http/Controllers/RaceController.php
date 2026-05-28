<?php

namespace App\Http\Controllers;

use App\Exceptions\IdempotencyKeyConflictException;
use App\Exceptions\IdempotencyKeyExpiredException;
use App\Exceptions\RaceAttemptFailedException;
use App\Exceptions\RaceAttemptPendingException;
use App\Exceptions\RaceStartRateLimitedException;
use App\Http\Requests\StartNpcRaceRequest;
use App\Models\Race;
use App\Models\RaceResult;
use App\Services\RaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class RaceController extends Controller
{
    public function __construct(
        private readonly RaceService $raceService,
    ) {}

    public function index(): View
    {
        $user = auth()->user();
        $profile = $user->playerProfile;
        $races = Race::query()
            ->active()
            ->unlockedForLevel($profile->level)
            ->orderBy('unlock_level')
            ->orderBy('name')
            ->get();

        $raceIdempotencyKeys = $races->mapWithKeys(
            fn (Race $race) => [$race->id => (string) Str::uuid()],
        );

        return view('races.index', [
            'profile' => $profile->load('activeCar.carModel'),
            'races' => $races,
            'raceIdempotencyKeys' => $raceIdempotencyKeys,
        ]);
    }

    public function store(StartNpcRaceRequest $request, Race $race): RedirectResponse|JsonResponse
    {
        try {
            $result = $this->raceService->startNpcRace(
                $request->user(),
                $race,
                $request->idempotencyKey(),
            );
        } catch (RaceAttemptPendingException|IdempotencyKeyConflictException|IdempotencyKeyExpiredException|RaceAttemptFailedException $exception) {
            return $this->raceStartConflictResponse($request, $exception->getMessage(), $exception);
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
            ->route('races.show', $result->raceResult)
            ->with('status', $result->replayed ? 'race-existing-result' : 'race-complete');
    }

    private function raceStartConflictResponse(
        StartNpcRaceRequest $request,
        string $message,
        \Throwable $exception,
    ): RedirectResponse|JsonResponse {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], Response::HTTP_CONFLICT);
        }

        return $this->raceStartErrorResponse($message);
    }

    private function raceStartErrorResponse(string $message): RedirectResponse
    {
        return back()
            ->withErrors(['race' => $message])
            ->withInput();
    }

    public function show(RaceResult $raceResult): View
    {
        if ($raceResult->user_id !== auth()->id()) {
            abort(403);
        }

        return view('races.show', [
            'raceResult' => $raceResult->load('race'),
        ]);
    }
}
