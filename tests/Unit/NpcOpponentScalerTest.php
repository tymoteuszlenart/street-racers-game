<?php

namespace Tests\Unit;

use App\Enums\RaceTier;
use App\Enums\RaceType;
use App\Models\Race;
use App\Services\NpcOpponentScaler;
use App\Services\RaceScoreCalculator;
use Database\Seeders\RaceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NpcOpponentScalerTest extends TestCase
{
    use RefreshDatabase;

    private NpcOpponentScaler $scaler;

    private RaceScoreCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RaceSeeder::class);
        $this->scaler = app(NpcOpponentScaler::class);
        $this->calculator = app(RaceScoreCalculator::class);
    }

    public function test_anchor_grows_with_player_level(): void
    {
        $levelOne = $this->scaler->anchorCarStatsForLevel(1);
        $levelTen = $this->scaler->anchorCarStatsForLevel(10);

        $this->assertGreaterThan($levelOne['power'], $levelTen['power']);
        $this->assertGreaterThan($levelOne['handling'], $levelTen['handling']);
    }

    public function test_tuning_increases_scaled_opponent_strength(): void
    {
        $race = Race::findByTypeAndTier(RaceType::Sprint, RaceTier::Pro);
        $driverStats = config('game.player.driver_stats.base');
        $starterCar = [
            'power' => 44,
            'acceleration' => 49,
            'grip' => 46,
            'handling' => 48,
            'condition_percent' => 100.0,
        ];
        $tunedCar = [
            'power' => 57,
            'acceleration' => 63,
            'grip' => 59,
            'handling' => 61,
            'condition_percent' => 100.0,
        ];

        $stockOpponent = $this->neutralOpponentScore($race, 1, $starterCar, $driverStats);
        $tunedOpponent = $this->neutralOpponentScore($race, 1, $tunedCar, $driverStats);

        $this->assertGreaterThan($stockOpponent, $tunedOpponent);
    }

    public function test_level_one_starter_win_rates_match_targets(): void
    {
        $driverStats = config('game.player.driver_stats.base');
        $starterCar = [
            'power' => 44,
            'acceleration' => 49,
            'grip' => 46,
            'handling' => 48,
            'condition_percent' => 100.0,
        ];

        $amateur = $this->simulateWinRate(RaceType::Sprint, RaceTier::Amateur, 1, $starterCar, $driverStats, 0.05);
        $semiPro = $this->simulateWinRate(RaceType::Sprint, RaceTier::SemiPro, 1, $starterCar, $driverStats, 0.05);
        $pro = $this->simulateWinRate(RaceType::Sprint, RaceTier::Pro, 1, $starterCar, $driverStats, 0.08);

        $this->assertGreaterThanOrEqual(0.85, $amateur);
        $this->assertGreaterThanOrEqual(0.58, $semiPro);
        $this->assertLessThanOrEqual(0.78, $semiPro);
        $this->assertGreaterThanOrEqual(0.42, $pro);
        $this->assertLessThanOrEqual(0.58, $pro);
    }

    public function test_tuned_starter_improves_win_rates_over_stock_build(): void
    {
        $driverStats = config('game.player.driver_stats.base');
        $starterCar = [
            'power' => 44,
            'acceleration' => 49,
            'grip' => 46,
            'handling' => 48,
            'condition_percent' => 100.0,
        ];
        $tunedCar = [
            'power' => 57,
            'acceleration' => 63,
            'grip' => 59,
            'handling' => 61,
            'condition_percent' => 100.0,
        ];

        $stockSemi = $this->simulateWinRate(RaceType::Sprint, RaceTier::SemiPro, 1, $starterCar, $driverStats, 0.05);
        $tunedSemi = $this->simulateWinRate(RaceType::Sprint, RaceTier::SemiPro, 1, $tunedCar, $driverStats, 0.05);
        $stockPro = $this->simulateWinRate(RaceType::Sprint, RaceTier::Pro, 1, $starterCar, $driverStats, 0.08);
        $tunedPro = $this->simulateWinRate(RaceType::Sprint, RaceTier::Pro, 1, $tunedCar, $driverStats, 0.08);

        $this->assertGreaterThan($stockSemi, $tunedSemi);
        $this->assertGreaterThan($stockPro, $tunedPro);
        $this->assertGreaterThanOrEqual(0.85, $tunedSemi);
        $this->assertGreaterThanOrEqual(0.60, $tunedPro);
        $this->assertLessThanOrEqual(0.78, $tunedPro);
    }

    /**
     * @param  array{power: int, acceleration: int, grip: int, handling: int, condition_percent: float}  $playerCarStats
     * @param  array{power: int, acceleration: int, grip: int, handling: int}  $playerDriverStats
     */
    private function simulateWinRate(
        RaceType $raceType,
        RaceTier $raceTier,
        int $playerLevel,
        array $playerCarStats,
        array $playerDriverStats,
        float $variance,
    ): float {
        $race = Race::findByTypeAndTier($raceType, $raceTier);
        $wins = 0;
        $iterations = 4000;

        mt_srand(42);

        for ($i = 0; $i < $iterations; $i++) {
            $scaledOpponent = $this->scaler->buildForRace(
                $race,
                $playerLevel,
                $playerCarStats,
                $playerDriverStats,
            );

            $playerRandom = $this->randomFactor($variance);
            $opponentRandom = $this->randomFactor($variance);
            $raceType = $race->resolvedRaceType();

            $playerScore = $this->calculator->calculate(
                $playerCarStats,
                $playerDriverStats,
                $playerRandom,
                $raceType,
            )['score'];

            $opponentScore = $this->calculator->calculate(
                $scaledOpponent['car'],
                $scaledOpponent['driver'],
                $opponentRandom,
                $raceType,
            )['score'];

            if ($playerScore > $opponentScore) {
                $wins++;
            }
        }

        return $wins / $iterations;
    }

    /**
     * @param  array{power: int, acceleration: int, grip: int, handling: int, condition_percent: float}  $playerCarStats
     * @param  array{power: int, acceleration: int, grip: int, handling: int}  $playerDriverStats
     */
    private function neutralOpponentScore(
        Race $race,
        int $playerLevel,
        array $playerCarStats,
        array $playerDriverStats,
    ): float {
        $scaledOpponent = $this->scaler->buildForRace(
            $race,
            $playerLevel,
            $playerCarStats,
            $playerDriverStats,
        );

        return $this->calculator->calculate(
            $scaledOpponent['car'],
            $scaledOpponent['driver'],
            0.0,
            $race->resolvedRaceType(),
        )['score'];
    }

    private function randomFactor(float $variance): float
    {
        $unit = mt_rand() / mt_getrandmax();

        return ($unit * 2 - 1) * $variance;
    }
}
