<?php

namespace App\Services;

use App\Enums\OpenCupStatus;
use App\Models\OpenCup;
use Illuminate\Support\Facades\DB;

class OpenCupAdvanceService
{
    public function __construct(
        private readonly OpenCupResolverService $resolverService,
    ) {}

    public function advanceAll(): int
    {
        $processed = 0;

        $this->advanceJoinWindows();
        $this->advanceSettlingWindows();

        $runningCups = OpenCup::query()
            ->where('status', OpenCupStatus::Running)
            ->orderBy('id')
            ->get();

        foreach ($runningCups as $cup) {
            $this->advanceRunningCup($cup);
            $processed++;
        }

        return $processed;
    }

    private function advanceJoinWindows(): void
    {
        OpenCup::query()
            ->where('status', OpenCupStatus::Open)
            ->where('join_ends_at', '<=', now())
            ->orderBy('id')
            ->each(function (OpenCup $cup): void {
                DB::transaction(function () use ($cup) {
                    $cup = OpenCup::query()->whereKey($cup->id)->lockForUpdate()->firstOrFail();

                    if ($cup->status !== OpenCupStatus::Open || $cup->join_ends_at->isFuture()) {
                        return;
                    }

                    $settlingMinutes = (int) config('game.open_cup.settling_minutes', 3);

                    $cup->update([
                        'status' => OpenCupStatus::Settling,
                        'settling_ends_at' => now()->addMinutes($settlingMinutes),
                    ]);
                });
            });
    }

    private function advanceSettlingWindows(): void
    {
        OpenCup::query()
            ->where('status', OpenCupStatus::Settling)
            ->where('settling_ends_at', '<=', now())
            ->orderBy('id')
            ->each(function (OpenCup $cup): void {
                DB::transaction(function () use ($cup) {
                    $cup = OpenCup::query()->whereKey($cup->id)->lockForUpdate()->firstOrFail();

                    if ($cup->status !== OpenCupStatus::Settling || $cup->settling_ends_at?->isFuture()) {
                        return;
                    }

                    $cup->update(['status' => OpenCupStatus::Running]);
                    $this->resolverService->setupMatches($cup->fresh());
                });
            });
    }

    private function advanceRunningCup(OpenCup $cup): void
    {
        DB::transaction(function () use ($cup) {
            $cup = OpenCup::query()->whereKey($cup->id)->lockForUpdate()->firstOrFail();

            if ($cup->status !== OpenCupStatus::Running) {
                return;
            }

            if (! $cup->matches()->exists()) {
                $this->resolverService->setupMatches($cup);
            }

            $this->resolverService->resolvePendingMatches($cup->fresh());
            $this->resolverService->applyRewardsIfReady($cup->fresh());
        });
    }
}
