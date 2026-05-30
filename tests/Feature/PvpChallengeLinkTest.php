<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PvpChallengeLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_pvp_index_pins_challenged_opponent_from_query_string(): void
    {
        $challenger = User::factory()->create(['name' => 'Challenger']);
        $challenger->playerProfile()->firstOrFail()->update(['active_car_id' => $challenger->cars()->firstOrFail()->id]);

        $alpha = User::factory()->create(['name' => 'Alpha Rival']);
        $alpha->playerProfile()->firstOrFail()->update(['active_car_id' => $alpha->cars()->firstOrFail()->id]);

        $target = User::factory()->create(['name' => 'Zulu Target']);
        $target->playerProfile()->firstOrFail()->update(['active_car_id' => $target->cars()->firstOrFail()->id]);

        $this->actingAs($challenger)
            ->get(route('pvp.index', ['challenge' => $target->id]))
            ->assertOk()
            ->assertSeeInOrder(['Zulu Target', 'Alpha Rival'], false);
    }
}
