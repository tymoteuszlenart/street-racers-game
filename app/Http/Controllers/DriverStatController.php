<?php

namespace App\Http\Controllers;

use App\Http\Requests\AllocateDriverStatsRequest;
use App\Services\DriverStatAllocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class DriverStatController extends Controller
{
    public function __construct(
        private readonly DriverStatAllocationService $driverStatAllocationService,
    ) {}

    public function store(AllocateDriverStatsRequest $request): RedirectResponse
    {
        $profile = $request->user()->playerProfile()->firstOrFail();

        try {
            $this->driverStatAllocationService->allocate($profile, $request->increments());
        } catch (ValidationException $exception) {
            return back()
                ->withErrors($exception->errors())
                ->withInput();
        }

        return back()->with('status', 'stats-allocated');
    }
}
