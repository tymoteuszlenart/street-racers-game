<?php

namespace App\Services;

use App\Enums\OpenCupStatus;
use App\Enums\TransactionCurrency;
use App\Enums\TransactionType;
use App\Models\Car;
use App\Models\OpenCup;
use App\Models\OpenCupEntry;
use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OpenCupRegistrationService
{
    public function __construct(
        private readonly OpenCupSnapshotBuilder $snapshotBuilder,
        private readonly TransactionService $transactionService,
    ) {}

    public function create(User $host): OpenCup
    {
        return DB::transaction(function () use ($host) {
            $profile = $this->lockProfile($host->id);
            $car = $this->requireActiveCar($profile);

            $this->assertUnlocked($profile);
            $this->assertNotInActiveCup($host->id);

            $entryFee = (int) config('game.open_cup.entry_fee_cash', 2000);
            $snapshot = $this->snapshotBuilder->build($profile, $car);
            $joinWindow = (int) config('game.open_cup.join_window_minutes', 45);

            $cup = OpenCup::query()->create([
                'host_user_id' => $host->id,
                'status' => OpenCupStatus::Open,
                'entry_fee_cash' => $entryFee,
                'host_snapshot' => $snapshot,
                'join_ends_at' => now()->addMinutes($joinWindow),
                'settling_ends_at' => null,
                'champion_entry_id' => null,
            ]);

            $this->chargeEntryFee($host->id, $profile, $entryFee, $cup);
            $this->createEntry($cup, $host, $snapshot);

            return $cup->fresh(['entries']);
        });
    }

    public function join(User $user, OpenCup $cup): OpenCupEntry
    {
        return DB::transaction(function () use ($user, $cup) {
            $cup = OpenCup::query()->whereKey($cup->id)->lockForUpdate()->firstOrFail();

            if (! $cup->isJoinable()) {
                throw ValidationException::withMessages([
                    'cup' => 'This Open Cup is not accepting new entrants.',
                ]);
            }

            if ($cup->entries()->where('user_id', $user->id)->exists()) {
                throw ValidationException::withMessages([
                    'cup' => 'You are already in this Open Cup.',
                ]);
            }

            $profile = $this->lockProfile($user->id);
            $car = $this->requireActiveCar($profile);

            $this->assertUnlocked($profile);
            $this->assertNotInActiveCup($user->id, $cup->id);

            $this->chargeEntryFee($user->id, $profile, $cup->entry_fee_cash, $cup);

            $snapshot = $this->snapshotBuilder->build($profile, $car);

            return $this->createEntry($cup, $user, $snapshot);
        });
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function createEntry(OpenCup $cup, User $user, array $snapshot): OpenCupEntry
    {
        return OpenCupEntry::query()->create([
            'open_cup_id' => $cup->id,
            'user_id' => $user->id,
            'display_name' => $user->name,
            'car_snapshot' => $snapshot,
            'solo_wins' => 0,
            'placement' => null,
            'rewards_applied' => false,
        ]);
    }

    private function lockProfile(int $userId): PlayerProfile
    {
        return PlayerProfile::query()
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function requireActiveCar(PlayerProfile $profile): Car
    {
        if ($profile->active_car_id === null) {
            throw ValidationException::withMessages([
                'active_car' => 'Equip an active car before entering an Open Cup.',
            ]);
        }

        return Car::query()
            ->whereKey($profile->active_car_id)
            ->where('user_id', $profile->user_id)
            ->firstOrFail();
    }

    private function assertUnlocked(PlayerProfile $profile): void
    {
        $unlockLevel = (int) config('game.open_cup.unlock_level', 5);

        if ($profile->level < $unlockLevel) {
            throw ValidationException::withMessages([
                'level' => "Reach level {$unlockLevel} to enter Open Cups.",
            ]);
        }
    }

    private function assertNotInActiveCup(int $userId, ?int $exceptCupId = null): void
    {
        $activeStatuses = OpenCupStatus::activeForPlayer();

        $query = OpenCupEntry::query()
            ->where('user_id', $userId)
            ->whereHas('openCup', fn ($builder) => $builder->whereIn('status', $activeStatuses));

        if ($exceptCupId !== null) {
            $query->where('open_cup_id', '!=', $exceptCupId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'cup' => 'Finish your current Open Cup before starting or joining another.',
            ]);
        }
    }

    private function chargeEntryFee(int $userId, PlayerProfile $profile, int $entryFee, ?OpenCup $cup): void
    {
        if ($profile->cash < $entryFee) {
            throw ValidationException::withMessages([
                'cash' => 'Not enough cash for the Open Cup entry fee.',
            ]);
        }

        $profile->cash -= $entryFee;
        $profile->save();

        $this->transactionService->record(
            userId: $userId,
            type: TransactionType::OpenCupEntry,
            currency: TransactionCurrency::Cash,
            amount: -$entryFee,
            balanceAfter: $profile->cash,
            sourceType: $cup?->getMorphClass() ?? OpenCup::class,
            sourceId: $cup?->id ?? 0,
        );
    }
}
