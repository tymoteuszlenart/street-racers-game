<?php

namespace App\Console\Commands;

use App\Enums\ClubTournamentStatus;
use App\Enums\TransactionCurrency;
use App\Enums\TransactionType;
use App\Models\Club;
use App\Models\ClubMember;
use App\Models\ClubTournament;
use App\Models\ClubTournamentRewardGrant;
use App\Models\PlayerProfile;
use App\Services\ClubTournamentSeasonRankingService;
use App\Services\ClubTournamentSeasonService;
use App\Services\PremiumFuelService;
use App\Services\TransactionService;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class CloseClubTournamentSeasonCommand extends Command
{
    protected $signature = 'club-tournament:close';

    protected $description = 'Close the active club tournament season, distribute rewards, and start the next season';

    public function handle(
        ClubTournamentSeasonService $seasonService,
        ClubTournamentSeasonRankingService $rankingService,
        PremiumFuelService $premiumFuelService,
        TransactionService $transactionService,
    ): int {
        $tournament = ClubTournament::query()
            ->where('status', ClubTournamentStatus::Active)
            ->where('ends_at', '<', now())
            ->first();

        if ($tournament === null) {
            $this->info('No active tournament season ready to close.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($tournament, $seasonService, $rankingService, $premiumFuelService, $transactionService) {
            $tournament = ClubTournament::query()
                ->whereKey($tournament->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($tournament->status !== ClubTournamentStatus::Active) {
                return;
            }

            $tournament->update(['status' => ClubTournamentStatus::Closed]);

            $topClubs = (int) config('game.tournaments.weekly_reward_top_clubs', 3);
            $rewardTable = config('game.tournaments.weekly_rewards', []);

            $rankedClubs = $rankingService->topClubsForSeason($tournament, $topClubs);

            foreach ($rankedClubs as $index => $club) {
                $rank = $index + 1;
                $rewards = $rewardTable[$rank] ?? null;

                if ($rewards === null) {
                    continue;
                }

                $members = ClubMember::query()
                    ->where('club_id', $club->id)
                    ->get();

                foreach ($members as $member) {
                    $this->grantRewardIfMissing(
                        $tournament,
                        $member->user_id,
                        $rewards,
                        $premiumFuelService,
                        $transactionService,
                    );
                }
            }

            $tournament->update(['status' => ClubTournamentStatus::RewardsDistributed]);

            Club::query()->update(['points' => 0]);

            $seasonService->createNextSeason();
        });

        $this->info('Club tournament season closed and rewards distributed.');

        return self::SUCCESS;
    }

    /**
     * @param  array{cash?: int, premium_fuel?: int}  $rewards
     */
    private function grantRewardIfMissing(
        ClubTournament $tournament,
        int $userId,
        array $rewards,
        PremiumFuelService $premiumFuelService,
        TransactionService $transactionService,
    ): void {
        try {
            ClubTournamentRewardGrant::query()->create([
                'club_tournament_id' => $tournament->id,
                'user_id' => $userId,
                'granted_payload' => $rewards,
                'created_at' => now(),
            ]);
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            return;
        }

        $profile = PlayerProfile::query()
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->first();

        if ($profile === null) {
            return;
        }

        $cash = (int) ($rewards['cash'] ?? 0);

        if ($cash > 0) {
            $profile->cash += $cash;
            $profile->save();

            $transactionService->record(
                userId: $userId,
                type: TransactionType::ClubTournamentReward,
                currency: TransactionCurrency::Cash,
                amount: $cash,
                balanceAfter: $profile->cash,
                sourceType: ClubTournamentRewardGrant::class,
                sourceId: ClubTournamentRewardGrant::query()
                    ->where('club_tournament_id', $tournament->id)
                    ->where('user_id', $userId)
                    ->value('id'),
            );
        }

        $premiumFuel = (int) ($rewards['premium_fuel'] ?? 0);

        if ($premiumFuel > 0) {
            $granted = $premiumFuelService->grant($profile, $premiumFuel);

            if ($granted > 0) {
                $transactionService->record(
                    userId: $userId,
                    type: TransactionType::ClubTournamentReward,
                    currency: TransactionCurrency::PremiumFuel,
                    amount: $granted,
                    balanceAfter: $profile->premium_fuel_current,
                    sourceType: ClubTournamentRewardGrant::class,
                    sourceId: ClubTournamentRewardGrant::query()
                        ->where('club_tournament_id', $tournament->id)
                        ->where('user_id', $userId)
                        ->value('id'),
                );
            }
        }
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;

        return in_array($sqlState, ['23000', '23505'], true);
    }
}
