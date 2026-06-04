<?php

namespace App\Services;

use App\Enums\RaceType;
use App\Models\Race;

class NpcOpponentScaler
{
    public function __construct(
        private readonly RaceScoreCalculator $scoreCalculator,
    ) {}

    /**
     * @param  array{power: int, acceleration: int, grip: int, handling: int, condition_percent: float}  $playerCarStats
     * @param  array{power: int, acceleration: int, grip: int, handling: int}  $playerDriverStats
     * @return array{
     *     car: array{power: int, acceleration: int, grip: int, handling: int, condition_percent: float},
     *     driver: array{power: int, acceleration: int, grip: int, handling: int},
     * }
     */
    public function buildForRace(
        Race $race,
        int $playerLevel,
        array $playerCarStats,
        array $playerDriverStats,
    ): array {
        $tier = config("game.npc_races.tiers.{$race->resolvedTier()->configKey()}") ?? [
            'strength_multiplier' => 1.0,
        ];

        $anchorCar = [
            ...$this->anchorCarStatsForLevel($playerLevel),
            'condition_percent' => 100.0,
        ];
        $anchorDriver = $this->anchorDriverStatsForLevel($playerLevel);

        $anchorScore = $this->neutralScore($anchorCar, $anchorDriver, $race->resolvedRaceType());
        $playerScore = $this->neutralScore($playerCarStats, $playerDriverStats, $race->resolvedRaceType());

        $blend = $this->tuningBlend($anchorScore, $playerScore);
        $referenceScore = $anchorScore + max(0.0, $playerScore - $anchorScore) * $blend;
        $targetScore = $referenceScore * (float) $tier['strength_multiplier'];
        $scale = $targetScore / max(0.01, $anchorScore);
        $raceType = $race->resolvedRaceType();

        return [
            'car' => [
                ...$this->scaleStatLine($anchorCar, $scale),
                'condition_percent' => 100.0,
            ],
            'driver' => $this->scaleStatLine($anchorDriver, $scale),
            'race_type' => $raceType->value,
        ];
    }

    /**
     * @return array{power: int, acceleration: int, grip: int, handling: int}
     */
    public function anchorCarStatsForLevel(int $playerLevel): array
    {
        $base = config('game.npc_races.anchor.car');
        $perLevel = config('game.npc_races.anchor.car_per_level');
        $levelsAboveStart = max(0, $playerLevel - 1);

        return [
            'power' => $this->growStat($base['power'], $perLevel['power'], $levelsAboveStart),
            'acceleration' => $this->growStat($base['acceleration'], $perLevel['acceleration'], $levelsAboveStart),
            'grip' => $this->growStat($base['grip'], $perLevel['grip'], $levelsAboveStart),
            'handling' => $this->growStat($base['handling'], $perLevel['handling'], $levelsAboveStart),
        ];
    }

    /**
     * @return array{power: int, acceleration: int, grip: int, handling: int}
     */
    public function anchorDriverStatsForLevel(int $playerLevel): array
    {
        $base = config('game.player.driver_stats.base');
        $perLevel = (float) config('game.npc_races.anchor.driver_stat_per_level');
        $levelsAboveStart = max(0, $playerLevel - 1);

        return [
            'power' => $this->growStat($base['power'], $perLevel, $levelsAboveStart),
            'acceleration' => $this->growStat($base['acceleration'], $perLevel, $levelsAboveStart),
            'grip' => $this->growStat($base['grip'], $perLevel, $levelsAboveStart),
            'handling' => $this->growStat($base['handling'], $perLevel, $levelsAboveStart),
        ];
    }

    /**
     * @param  array{power: int, acceleration: int, grip: int, handling: int, condition_percent: float}  $carStats
     * @param  array{power: int, acceleration: int, grip: int, handling: int}  $driverStats
     */
    private function neutralScore(array $carStats, array $driverStats, RaceType $raceType): float
    {
        return $this->scoreCalculator->calculate($carStats, $driverStats, 0.0, $raceType)['score'];
    }

    private function tuningBlend(float $anchorScore, float $playerScore): float
    {
        $excessRatio = max(0.0, $playerScore - $anchorScore) / max(1.0, $anchorScore);
        $maxBlend = (float) config('game.npc_races.tuning_blend.max');
        $slope = (float) config('game.npc_races.tuning_blend.slope');

        return min($maxBlend, $excessRatio * $slope);
    }

    /**
     * @param  array{power: int, acceleration: int, grip: int, handling: int}  $stats
     * @return array{power: int, acceleration: int, grip: int, handling: int}
     */
    private function scaleStatLine(array $stats, float $scale): array
    {
        $scaled = [];

        foreach ($stats as $key => $value) {
            $scaled[$key] = max(1, (int) round($value * $scale));
        }

        return $scaled;
    }

    private function growStat(int|float $base, float $perLevel, int $levelsAboveStart): int
    {
        return max(1, (int) round($base + ($perLevel * $levelsAboveStart)));
    }
}
