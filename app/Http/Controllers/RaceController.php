<?php

namespace App\Http\Controllers;

use App\Exceptions\IdempotencyKeyExpiredException;
use App\Exceptions\IdempotencyKeyConflictException;
use App\Exceptions\RaceAttemptFailedException;
use App\Exceptions\RaceAttemptPendingException;
use App\Http\Requests\StartNpcRaceRequest;
use App\Models\Race;
use App\Models\RaceResult;
use App\Services\RaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

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

        return view('races.index', [
            'profile' => $profile->load('activeCar.carModel'),
            'races' => $races,
        ]);
    }

    public function store(StartNpcRaceRequest $request, Race $race): RedirectResponse
    {
        try {
            $result = $this->raceService->startNpcRace(
                $request->user(),
                $race,
                $request->idempotencyKey(),
            );
        } catch (RaceAttemptPendingException) {
            abort(409, 'A race with this idempotency key is already in progress.');
        } catch (IdempotencyKeyConflictException) {
            abort(409, 'This idempotency key was already used for a different race request.');
        } catch (RaceAttemptFailedException) {
            abort(422, 'This idempotency key was used by a failed race attempt. Use a new key to retry.');
        } catch (IdempotencyKeyExpiredException) {
            abort(422, 'The idempotency key has expired. Use a new key to start a race.');
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        return redirect()
            ->route('races.show', $result->raceResult)
            ->with('status', $result->replayed ? 'race-existing-result' : 'race-complete');
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
