<?php

namespace Tests\Unit;

use App\Models\Race;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RaceOpponentDriverStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_npc_tiers_have_distinct_opponent_driver_stats(): void
    {
        $amateur = Race::query()->where('name', 'Amateur')->firstOrFail();
        $semiPro = Race::query()->where('name', 'Semi-Pro')->firstOrFail();
        $pro = Race::query()->where('name', 'Pro')->firstOrFail();

        $this->assertSame(['power' => 1, 'acceleration' => 1, 'grip' => 1, 'handling' => 1], $amateur->opponentDriverStats());
        $this->assertSame(['power' => 3, 'acceleration' => 3, 'grip' => 3, 'handling' => 3], $semiPro->opponentDriverStats());
        $this->assertSame(['power' => 6, 'acceleration' => 5, 'grip' => 5, 'handling' => 4], $pro->opponentDriverStats());
    }

    public function test_all_npc_races_are_available_from_level_one(): void
    {
        foreach (['Amateur', 'Semi-Pro', 'Pro'] as $name) {
            $race = Race::query()->where('name', $name)->firstOrFail();
            $this->assertSame(1, $race->unlock_level);
        }
    }
}
