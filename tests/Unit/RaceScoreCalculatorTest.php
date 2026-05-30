<?php

namespace Tests\Unit;

use App\Services\RaceScoreCalculator;
use Tests\TestCase;

class RaceScoreCalculatorTest extends TestCase
{
    public function test_higher_driver_stats_increase_race_bonus(): void
    {
        $calculator = app(RaceScoreCalculator::class);

        $stats = [
            'power' => 50,
            'acceleration' => 50,
            'grip' => 50,
            'handling' => 50,
            'condition_percent' => 100,
        ];

        $low = $calculator->calculate($stats, [
            'power' => 1,
            'acceleration' => 1,
            'grip' => 1,
            'handling' => 1,
        ], 0.0);

        $high = $calculator->calculate($stats, [
            'power' => 10,
            'acceleration' => 10,
            'grip' => 10,
            'handling' => 10,
        ], 0.0);

        $this->assertGreaterThan($low['score'], $high['score']);
        $this->assertGreaterThan($low['breakdown']['driver_bonus'], $high['breakdown']['driver_bonus']);
    }
}
