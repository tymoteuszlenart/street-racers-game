<?php

namespace Tests\Unit;

use App\Services\OpenCupRewardCalculator;
use Tests\TestCase;

class OpenCupRewardCalculatorTest extends TestCase
{
    public function test_solo_three_wins_outreward_two_wins_and_default(): void
    {
        $calculator = app(OpenCupRewardCalculator::class);
        $fee = (int) config('game.open_cup.entry_fee_cash', 2000);

        $three = $calculator->soloRewardsForWins(3, $fee);
        $two = $calculator->soloRewardsForWins(2, $fee);
        $zero = $calculator->soloRewardsForWins(0, $fee);

        $this->assertSame(1200, $three['cash']);
        $this->assertSame(4, $three['cups']);
        $this->assertSame(1200, $two['cash']);
        $this->assertSame(3, $two['cups']);
        $this->assertSame(1200, $zero['cash']);
        $this->assertSame(1, $zero['cups']);
    }

    public function test_champion_cash_is_forty_percent_of_other_entrant_fees(): void
    {
        $calculator = app(OpenCupRewardCalculator::class);
        $fee = 2000;

        $this->assertSame(2400, $calculator->championCash($fee, 4));
        $this->assertSame(0, $calculator->championCash($fee, 1));
    }

    public function test_bracket_win_cups_matches_config(): void
    {
        $calculator = app(OpenCupRewardCalculator::class);

        $this->assertSame(3, $calculator->bracketWinCups());
    }
}
