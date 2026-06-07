<?php

namespace App\Services;

use App\Enums\OpenCupMatchPhase;
use App\Enums\OpenCupStatus;
use App\Enums\RaceAttemptType;
use App\Enums\TransactionCurrency;
use App\Enums\TransactionType;
use App\Models\OpenCup;
use App\Models\OpenCupEntry;
use App\Models\OpenCupMatch;
use App\Models\PlayerProfile;
use App\Models\RaceResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OpenCupResolverService
{
    /** @var (callable(): float)|null */
    private $randomUnit;

    public function __construct(
        private readonly RaceScoreCalculator $scoreCalculator,
        private readonly NpcOpponentScaler $npcOpponentScaler,
        private readonly OpenCupRewardCalculator $rewardCalculator,
        private readonly TransactionService $transactionService,
    ) {}

    /**
     * @param  callable(): float  $randomUnit
     */
    public function withRandomUnit(callable $randomUnit): self
    {
        $clone = clone $this;
        $clone->randomUnit = $randomUnit;

        return $clone;
    }

    public function setupMatches(OpenCup $cup): void
    {
        if ($cup->matches()->exists()) {
            return;
        }

        $entries = $cup->entries()->orderBy('id')->get();
        $entrantCount = $entries->count();

        if ($entrantCount === 1) {
            $this->setupSoloMatches($cup, $entries->first());

            return;
        }

        $this->setupQualifyingMatches($cup, $entries);
        $this->setupInitialBracket($cup, $entries);
    }

    public function resolvePendingMatches(OpenCup $cup): void
    {
        $pending = $cup->matches()
            ->whereNull('race_result_id')
            ->where('both_eliminated', false)
            ->orderBy('round')
            ->orderBy('match_order')
            ->get();

        foreach ($pending as $match) {
            if ($match->phase === OpenCupMatchPhase::Bracket
                && ($match->entry_a_id === null xor $match->entry_b_id === null)) {
                $this->resolveBracketBye($match);

                continue;
            }

            if ($match->phase === OpenCupMatchPhase::Bracket
                && $match->entry_a_id === null
                && $match->entry_b_id === null) {
                continue;
            }

            $this->resolveMatch($cup, $match);
        }

        if ($cup->entries()->count() > 1) {
            $this->maybeCreateNextBracketRound($cup);
        }
    }

    public function applyRewardsIfReady(OpenCup $cup): void
    {
        if ($cup->status !== OpenCupStatus::Running) {
            return;
        }

        if ($cup->matches()->whereNull('race_result_id')->where('both_eliminated', false)->exists()) {
            return;
        }

        if ($cup->entries()->where('rewards_applied', false)->doesntExist()) {
            $cup->update(['status' => OpenCupStatus::Completed]);

            return;
        }

        DB::transaction(function () use ($cup) {
            $cup = OpenCup::query()->whereKey($cup->id)->lockForUpdate()->firstOrFail();
            $entries = $cup->entries()->orderBy('id')->lockForUpdate()->get();
            $entrantCount = $entries->count();

            if ($entrantCount === 1) {
                $this->applySoloRewards($cup, $entries->first());
                $cup->update([
                    'status' => OpenCupStatus::Completed,
                    'champion_entry_id' => $entries->first()->id,
                ]);

                return;
            }

            $champion = $this->determineChampion($cup, $entries);
            $this->applyMultiplayerRewards($cup, $entries, $champion);

            $cup->update([
                'status' => OpenCupStatus::Completed,
                'champion_entry_id' => $champion?->id,
            ]);
        });
    }

    private function setupSoloMatches(OpenCup $cup, OpenCupEntry $entry): void
    {
        $raceCount = (int) config('game.open_cup.solo_npc_races', 3);

        for ($index = 1; $index <= $raceCount; $index++) {
            OpenCupMatch::query()->create([
                'open_cup_id' => $cup->id,
                'phase' => OpenCupMatchPhase::Solo,
                'round' => 1,
                'match_order' => $index,
                'entry_id' => $entry->id,
            ]);
        }
    }

    /**
     * @param  Collection<int, OpenCupEntry>  $entries
     */
    private function setupQualifyingMatches(OpenCup $cup, Collection $entries): void
    {
        foreach ($entries as $index => $entry) {
            OpenCupMatch::query()->create([
                'open_cup_id' => $cup->id,
                'phase' => OpenCupMatchPhase::Qualifying,
                'round' => 1,
                'match_order' => $index + 1,
                'entry_id' => $entry->id,
            ]);
        }
    }

    /**
     * @param  Collection<int, OpenCupEntry>  $entries
     */
    private function setupInitialBracket(OpenCup $cup, Collection $entries): void
    {
        $ordered = $entries->values();
        $pairIndex = 0;

        for ($i = 0; $i < $ordered->count(); $i += 2) {
            $pairIndex++;
            $entryA = $ordered->get($i);
            $entryB = $ordered->get($i + 1);

            OpenCupMatch::query()->create([
                'open_cup_id' => $cup->id,
                'phase' => OpenCupMatchPhase::Bracket,
                'round' => 1,
                'match_order' => $pairIndex,
                'entry_a_id' => $entryA?->id,
                'entry_b_id' => $entryB?->id,
            ]);
        }
    }

    private function resolveMatch(OpenCup $cup, OpenCupMatch $match): void
    {
        if ($match->phase === OpenCupMatchPhase::Bracket) {
            $this->resolveBracketMatch($cup, $match);

            return;
        }

        $entry = $match->entry ?? OpenCupEntry::query()->findOrFail($match->entry_id);
        $snapshot = $entry->car_snapshot;
        $playerStats = $snapshot['stats'];
        $driverStats = $snapshot['driver'];
        $level = (int) ($snapshot['level'] ?? 1);

        $variance = (float) config('game.open_cup.random_factor_variance', 0.03);
        $randomUnit = $this->randomUnitCallable();
        $playerRandom = $this->scoreCalculator->randomFactorInRange($variance, $randomUnit);
        $opponentRandom = $this->scoreCalculator->randomFactorInRange($variance, $randomUnit);

        $scaledOpponent = $this->scaledNpcForSnapshot($playerStats, $driverStats, $level);

        $playerOutcome = $this->scoreCalculator->calculate($playerStats, $driverStats, $playerRandom);
        $opponentOutcome = $this->scoreCalculator->calculate(
            $scaledOpponent['car'],
            $scaledOpponent['driver'],
            $opponentRandom,
        );

        $playerScore = $playerOutcome['score'];
        $opponentScore = $opponentOutcome['score'];
        $won = $playerScore > $opponentScore;
        $isTie = $playerScore === $opponentScore;

        $raceResult = RaceResult::query()->create([
            'user_id' => $entry->user_id,
            'attempt_type' => RaceAttemptType::OpenCup,
            'race_id' => null,
            'pvp_race_id' => null,
            'club_tournament_id' => null,
            'open_cup_id' => $cup->id,
            'won' => $won,
            'is_tie' => $isTie,
            'player_score' => $playerScore,
            'opponent_score' => $opponentScore,
            'score_breakdown' => [
                'player' => $playerOutcome['breakdown'],
                'opponent' => $opponentOutcome['breakdown'],
                'opponent_scaled' => $scaledOpponent,
            ],
            'random_factor' => $playerRandom,
        ]);

        $match->update([
            'race_result_id' => $raceResult->id,
            'winner_entry_id' => $won ? $entry->id : null,
            'both_eliminated' => $isTie,
        ]);

        if ($match->phase === OpenCupMatchPhase::Solo && $won) {
            $entry->increment('solo_wins');
        }
    }

    private function resolveBracketMatch(OpenCup $cup, OpenCupMatch $match): void
    {
        $entryA = OpenCupEntry::query()->findOrFail($match->entry_a_id);
        $entryB = OpenCupEntry::query()->findOrFail($match->entry_b_id);

        $snapshotA = $entryA->car_snapshot;
        $snapshotB = $entryB->car_snapshot;

        $variance = (float) config('game.open_cup.random_factor_variance', 0.03);
        $sharedRandom = $this->scoreCalculator->randomFactorInRange($variance, $this->randomUnitCallable());

        $outcomeA = $this->scoreCalculator->calculate(
            $snapshotA['stats'],
            $snapshotA['driver'],
            $sharedRandom,
        );
        $outcomeB = $this->scoreCalculator->calculate(
            $snapshotB['stats'],
            $snapshotB['driver'],
            $sharedRandom,
        );

        $scoreA = $outcomeA['score'];
        $scoreB = $outcomeB['score'];
        $isTie = $scoreA === $scoreB;
        $aWins = $scoreA > $scoreB;

        $raceResult = RaceResult::query()->create([
            'user_id' => $entryA->user_id,
            'attempt_type' => RaceAttemptType::OpenCup,
            'open_cup_id' => $cup->id,
            'won' => $aWins,
            'is_tie' => $isTie,
            'player_score' => $scoreA,
            'opponent_score' => $scoreB,
            'score_breakdown' => [
                'entry_a' => $outcomeA['breakdown'],
                'entry_b' => $outcomeB['breakdown'],
                'entry_a_id' => $entryA->id,
                'entry_b_id' => $entryB->id,
            ],
            'random_factor' => $sharedRandom,
        ]);

        $match->update([
            'race_result_id' => $raceResult->id,
            'winner_entry_id' => $isTie ? null : ($aWins ? $entryA->id : $entryB->id),
            'both_eliminated' => $isTie,
        ]);

        if (! $isTie) {
            $winner = $aWins ? $entryA : $entryB;
            $this->grantBracketWinCups($winner);
        }
    }

    private function resolveBracketBye(OpenCupMatch $match): void
    {
        $winnerId = $match->entry_a_id ?? $match->entry_b_id;

        $match->update([
            'winner_entry_id' => $winnerId,
            'both_eliminated' => false,
        ]);
    }

    private function maybeCreateNextBracketRound(OpenCup $cup): void
    {
        $maxRound = (int) $cup->matches()
            ->where('phase', OpenCupMatchPhase::Bracket)
            ->max('round');

        $currentRoundMatches = $cup->matches()
            ->where('phase', OpenCupMatchPhase::Bracket)
            ->where('round', $maxRound)
            ->get();

        if ($currentRoundMatches->isEmpty()) {
            return;
        }

        foreach ($currentRoundMatches as $match) {
            if (! $match->isResolved()) {
                return;
            }
        }

        $winners = $currentRoundMatches
            ->map(fn (OpenCupMatch $match) => $match->winner_entry_id)
            ->filter()
            ->values();

        if ($winners->count() <= 1) {
            return;
        }

        $nextRound = $maxRound + 1;

        if ($cup->matches()
            ->where('phase', OpenCupMatchPhase::Bracket)
            ->where('round', $nextRound)
            ->exists()) {
            return;
        }

        $pairIndex = 0;

        for ($i = 0; $i < $winners->count(); $i += 2) {
            $pairIndex++;
            $entryAId = $winners->get($i);
            $entryBId = $winners->get($i + 1);

            OpenCupMatch::query()->create([
                'open_cup_id' => $cup->id,
                'phase' => OpenCupMatchPhase::Bracket,
                'round' => $nextRound,
                'match_order' => $pairIndex,
                'entry_a_id' => $entryAId,
                'entry_b_id' => $entryBId,
            ]);
        }
    }

    private function determineChampion(OpenCup $cup, Collection $entries): ?OpenCupEntry
    {
        $finalRound = (int) $cup->matches()
            ->where('phase', OpenCupMatchPhase::Bracket)
            ->max('round');

        $finalMatch = $cup->matches()
            ->where('phase', OpenCupMatchPhase::Bracket)
            ->where('round', $finalRound)
            ->orderBy('match_order')
            ->first();

        if ($finalMatch === null || $finalMatch->both_eliminated || $finalMatch->winner_entry_id === null) {
            return null;
        }

        return $entries->firstWhere('id', $finalMatch->winner_entry_id);
    }

    private function applySoloRewards(OpenCup $cup, OpenCupEntry $entry): void
    {
        if ($entry->rewards_applied) {
            return;
        }

        $rewards = $this->rewardCalculator->soloRewardsForWins($entry->solo_wins, $cup->entry_fee_cash);
        $this->grantRewardsToEntry($entry, $rewards['cash'], $rewards['cups'], $cup);
        $entry->update(['placement' => 1]);
    }

    /**
     * @param  Collection<int, OpenCupEntry>  $entries
     */
    private function applyMultiplayerRewards(OpenCup $cup, Collection $entries, ?OpenCupEntry $champion): void
    {
        $participation = $this->rewardCalculator->participationRewards();
        $championCash = $this->rewardCalculator->championCash($cup->entry_fee_cash, $entries->count());

        foreach ($entries as $entry) {
            if ($entry->rewards_applied) {
                continue;
            }

            $cash = $participation['cash'];
            $cups = $participation['cups'];

            if ($champion !== null && $entry->id === $champion->id) {
                $cash += $championCash;
                $entry->update(['placement' => 1]);
            }

            $this->grantRewardsToEntry($entry, $cash, $cups, $cup);
        }
    }

    private function grantBracketWinCups(OpenCupEntry $entry): void
    {
        if ($entry->user_id === null) {
            return;
        }

        $profile = PlayerProfile::query()
            ->where('user_id', $entry->user_id)
            ->lockForUpdate()
            ->first();

        if ($profile === null) {
            return;
        }

        $cups = $this->rewardCalculator->bracketWinCups();
        $profile->cups += $cups;
        $profile->save();

        $cup = $entry->openCup ?? OpenCup::query()->findOrFail($entry->open_cup_id);

        $this->transactionService->record(
            userId: $entry->user_id,
            type: TransactionType::OpenCupReward,
            currency: TransactionCurrency::Cups,
            amount: $cups,
            balanceAfter: $profile->cups,
            sourceType: $cup->getMorphClass(),
            sourceId: $cup->id,
        );
    }

    /**
     * @param  array{cash: int, cups: int}  $rewards
     */
    private function grantRewardsToEntry(OpenCupEntry $entry, int $cash, int $cups, OpenCup $cup): void
    {
        if ($entry->user_id === null) {
            $entry->update(['rewards_applied' => true]);

            return;
        }

        $profile = PlayerProfile::query()
            ->where('user_id', $entry->user_id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($cash > 0) {
            $profile->cash += $cash;
            $this->transactionService->record(
                userId: $entry->user_id,
                type: TransactionType::OpenCupReward,
                currency: TransactionCurrency::Cash,
                amount: $cash,
                balanceAfter: $profile->cash,
                sourceType: $cup->getMorphClass(),
                sourceId: $cup->id,
            );
        }

        if ($cups > 0) {
            $profile->cups += $cups;
            $this->transactionService->record(
                userId: $entry->user_id,
                type: TransactionType::OpenCupReward,
                currency: TransactionCurrency::Cups,
                amount: $cups,
                balanceAfter: $profile->cups,
                sourceType: $cup->getMorphClass(),
                sourceId: $cup->id,
            );
        }

        $profile->save();
        $entry->update(['rewards_applied' => true]);
    }

    /**
     * @param  array{power: int, acceleration: int, grip: int, handling: int, condition_percent: float}  $playerStats
     * @param  array{power: int, acceleration: int, grip: int, handling: int}  $driverStats
     * @return array{car: array{power: int, acceleration: int, grip: int, handling: int, condition_percent: float}, driver: array{power: int, acceleration: int, grip: int, handling: int}}
     */
    private function scaledNpcForSnapshot(array $playerStats, array $driverStats, int $level): array
    {
        $anchorCar = [
            ...$this->npcOpponentScaler->anchorCarStatsForLevel($level),
            'condition_percent' => 100.0,
        ];
        $anchorDriver = $this->npcOpponentScaler->anchorDriverStatsForLevel($level);

        $anchorScore = $this->scoreCalculator->calculate($anchorCar, $anchorDriver, 0.0)['score'];
        $playerScore = $this->scoreCalculator->calculate($playerStats, $driverStats, 0.0)['score'];
        $scale = max(0.87, min(1.05, $playerScore / max(0.01, $anchorScore)));

        return [
            'car' => [
                'power' => max(1, (int) round($anchorCar['power'] * $scale)),
                'acceleration' => max(1, (int) round($anchorCar['acceleration'] * $scale)),
                'grip' => max(1, (int) round($anchorCar['grip'] * $scale)),
                'handling' => max(1, (int) round($anchorCar['handling'] * $scale)),
                'condition_percent' => 100.0,
            ],
            'driver' => [
                'power' => max(1, (int) round($anchorDriver['power'] * $scale)),
                'acceleration' => max(1, (int) round($anchorDriver['acceleration'] * $scale)),
                'grip' => max(1, (int) round($anchorDriver['grip'] * $scale)),
                'handling' => max(1, (int) round($anchorDriver['handling'] * $scale)),
            ],
        ];
    }

    private function randomUnitCallable(): callable
    {
        return $this->randomUnit ?? fn (): float => mt_rand() / mt_getrandmax();
    }
}
