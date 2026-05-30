<?php

namespace App\Services;

class RaceScoreCalculator
{
    /**
     * @param  array{power: float|int, acceleration: float|int, grip: float|int, handling: float|int, condition_percent: float}  $stats
     * @param  array{power: int, acceleration: int, grip: int, handling: int}  $driverStats
     * @return array{score: float, breakdown: array<string, float|int|bool>}
     */
    public function calculate(array $stats, array $driverStats, float $randomFactor): array
    {
        $base = ($stats['power'] * 0.35)
            + ($stats['acceleration'] * 0.25)
            + ($stats['grip'] * 0.20)
            + ($stats['handling'] * 0.10);

        $driverBonus = $this->driverRaceBonus($driverStats);
        $conditionPenalty = $this->conditionPenalty($stats['condition_percent']);
        $randomAdjustment = $base * $randomFactor;
        $score = max(0, $base + $driverBonus + $randomAdjustment - $conditionPenalty);

        return [
            'score' => round($score, 4),
            'breakdown' => [
                'base' => round($base, 4),
                'driver_bonus' => round($driverBonus, 4),
                'driver_stats' => $driverStats,
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
    public function driverRaceBonus(array $driverStats): float
    {
        $weights = config('game.player.driver_stats.race_weights', []);
        $bonus = 0.0;

        foreach (['power', 'acceleration', 'grip', 'handling'] as $stat) {
            $bonus += ($driverStats[$stat] ?? 0) * (float) ($weights[$stat] ?? 0);
        }

        return $bonus;
    }

    public function conditionPenalty(float $conditionPercent): float
    {
        return match (true) {
            $conditionPercent >= 90 => 0,
            $conditionPercent >= 70 => 2,
            $conditionPercent >= 50 => 5,
            default => 10,
        };
    }

    public function randomFactorInRange(float $variance, callable $randomUnit): float
    {
        $unit = $randomUnit();

        return ($unit * 2 - 1) * $variance;
    }
}
