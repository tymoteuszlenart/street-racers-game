<?php

namespace App\Http\Controllers;

use App\Enums\OpenCupStatus;
use App\Models\OpenCup;
use App\Models\OpenCupEntry;
use App\Services\OpenCupRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OpenCupController extends Controller
{
    public function __construct(
        private readonly OpenCupRegistrationService $registrationService,
    ) {}

    public function index(): View
    {
        $user = auth()->user();
        $profile = $user->playerProfile;

        $openCups = OpenCup::query()
            ->where('status', OpenCupStatus::Open)
            ->where('join_ends_at', '>', now())
            ->with(['host', 'entries'])
            ->orderByDesc('join_ends_at')
            ->get();

        $activeEntry = OpenCupEntry::query()
            ->where('user_id', $user->id)
            ->whereHas('openCup', fn ($query) => $query->whereIn('status', OpenCupStatus::activeForPlayer()))
            ->with('openCup')
            ->first();

        return view('cups.index', [
            'profile' => $profile,
            'openCups' => $openCups,
            'activeEntry' => $activeEntry,
            'entryFee' => (int) config('game.open_cup.entry_fee_cash', 2000),
        ]);
    }

    public function store(): RedirectResponse
    {
        try {
            $cup = $this->registrationService->create(auth()->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()
            ->route('cups.show', $cup)
            ->with('status', 'cup-created');
    }

    public function show(OpenCup $cup): View
    {
        $user = auth()->user();
        $cup->load(['host', 'entries.user', 'matches', 'championEntry']);

        $viewerEntry = $cup->entries->firstWhere('user_id', $user->id);
        $joinClosesInSeconds = $cup->status === OpenCupStatus::Open && $cup->join_ends_at->isFuture()
            ? now()->diffInSeconds($cup->join_ends_at)
            : null;

        return view('cups.show', [
            'cup' => $cup,
            'profile' => $user->playerProfile,
            'viewerEntry' => $viewerEntry,
            'joinClosesInSeconds' => $joinClosesInSeconds,
            'entryFee' => $cup->entry_fee_cash,
            'canJoin' => $viewerEntry === null && $cup->isJoinable(),
        ]);
    }

    public function join(OpenCup $cup): RedirectResponse
    {
        try {
            $this->registrationService->join(auth()->user(), $cup);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()
            ->route('cups.show', $cup)
            ->with('status', 'cup-joined');
    }
}
