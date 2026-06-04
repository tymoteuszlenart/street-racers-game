<?php

namespace App\Services;

use App\Enums\RaceType;

class RaceScoreCalculator
{
    public function __construct(
        private readonly ConditionService $conditionService,
    ) {}

    /**
     * @param  array{power: float|int, acceleration: float|int, grip: float|int, handling: float|int, condition_percent: float}  $stats
     * @param  array{power: int, acceleration: int, grip: int, handling: int}  $driverStats
     * @return array{score: float, breakdown: array<string, float|int|bool|string|array<string, float>>}
     */
    public function calculate(
        array $stats,
        array $driverStats,
        float $randomFactor,
        RaceType $raceType = RaceType::Circuit,
    ): array {
        $base = ($stats['power'] * 0.35)
            + ($stats['acceleration'] * 0.25)
            + ($stats['grip'] * 0.20)
            + ($stats['handling'] * 0.10);

        $driverBonus = $this->driverRaceBonus($driverStats, $raceType);
        $conditionPenalty = $this->conditionPenalty($stats['condition_percent']);
        $randomAdjustment = $base * $randomFactor;
        $score = max(0, $base + $driverBonus + $randomAdjustment - $conditionPenalty);

        return [
            'score' => round($score, 4),
            'breakdown' => [
                'base' => round($base, 4),
                'driver_bonus' => round($driverBonus, 4),
                'driver_stats' => $driverStats,
                'race_type' => $raceType->value,
                'driver_affinity' => $this->affinityWeights($raceType),
                'random_factor' => $randomFactor,
                'random_adjustment' => round($randomAdjustment, 4),
                'condition_percent' => $stats['condition_percent'],
                'condition_penalty' => $conditionPenalty,
            ],
        ];
    }

    /**
     * @param  array{power: int, acceleration: int, grip: int, handling: int}  $driverStats
     */
    public function driverRaceBonus(array $driverStats, RaceType $raceType = RaceType::Circuit): float
    {
        $weights = $this->affinityWeights($raceType);
        $bonus = 0.0;

        foreach (['power', 'acceleration', 'grip', 'handling'] as $stat) {
            $bonus += ($driverStats[$stat] ?? 0) * (float) ($weights[$stat] ?? 0);
        }

        return $bonus;
    }

    /**
     * @return array{power: float, acceleration: float, grip: float, handling: float}
     */
    public function affinityWeights(RaceType $raceType): array
    {
        $configured = config("game.player.driver_stats.race_type_affinities.{$raceType->value}", []);
        $fallback = config('game.player.driver_stats.race_type_affinities.circuit', []);

        return [
            'power' => (float) ($configured['power'] ?? $fallback['power'] ?? 0.12),
            'acceleration' => (float) ($configured['acceleration'] ?? $fallback['acceleration'] ?? 0.12),
            'grip' => (float) ($configured['grip'] ?? $fallback['grip'] ?? 0.12),
            'handling' => (float) ($configured['handling'] ?? $fallback['handling'] ?? 0.12),
        ];
    }

    public function defaultRaceType(): RaceType
    {
        $value = config('game.player.driver_stats.default_race_type', RaceType::Circuit->value);

        return RaceType::tryFrom((string) $value) ?? RaceType::Circuit;
    }

    public function conditionPenalty(float $conditionPercent): float
    {
        return $this->conditionService->carScorePenaltyFromPercent($conditionPercent);
    }

    public function randomFactorInRange(float $variance, callable $randomUnit): float
    {
        $unit = $randomUnit();

        return ($unit * 2 - 1) * $variance;
    }
}
