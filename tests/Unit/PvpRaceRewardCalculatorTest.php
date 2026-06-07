<?php

namespace Tests\Unit;

use App\Services\PvpRaceRewardCalculator;
use Tests\TestCase;

class PvpRaceRewardCalculatorTest extends TestCase
{
    public function test_level_one_win_beats_amateur_npc_rewards(): void
    {
        $rewards = app(PvpRaceRewardCalculator::class)->forOpponentLevel(1, won: true);

        $this->assertGreaterThan(100, $rewards['cash']);
        $this->assertGreaterThan(3, $rewards['reputation']);
    }

    public function test_higher_opponent_level_increases_win_rewards(): void
    {
        $calculator = app(PvpRaceRewardCalculator::class);

        $low = $calculator->forOpponentLevel(1, won: true);
        $high = $calculator->forOpponentLevel(10, won: true);

        $this->assertGreaterThan($low['cash'], $high['cash']);
        $this->assertGreaterThan($low['reputation'], $high['reputation']);
    }

    public function test_loss_grants_smaller_rewards_than_win_at_same_level(): void
    {
        $calculator = app(PvpRaceRewardCalculator::class);

        $win = $calculator->forOpponentLevel(5, won: true);
        $loss = $calculator->forOpponentLevel(5, won: false);

        $this->assertGreaterThan($loss['cash'], $win['cash']);
        $this->assertGreaterThan($loss['reputation'], $win['reputation']);
    }
}
