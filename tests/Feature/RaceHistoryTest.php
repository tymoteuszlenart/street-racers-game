<?php

namespace Tests\Feature;

use App\Enums\RaceAttemptType;
use App\Enums\RaceTier;
use App\Enums\RaceType;
use App\Models\Race;
use App\Models\RaceResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RaceHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_race_history(): void
    {
        $this->get(route('race-history.index'))->assertRedirect(route('login'));
    }

    public function test_player_sees_own_race_results_newest_first(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $race = Race::findByTypeAndTier(RaceType::Sprint, RaceTier::Amateur);

        $older = RaceResult::query()->create([
            'user_id' => $user->id,
            'attempt_type' => RaceAttemptType::Npc,
            'race_id' => $race->id,
            'won' => true,
            'is_tie' => false,
            'player_score' => 80,
            'opponent_score' => 70,
            'random_factor' => 0.1,
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        $newer = RaceResult::query()->create([
            'user_id' => $user->id,
            'attempt_type' => RaceAttemptType::Npc,
            'race_id' => $race->id,
            'won' => false,
            'is_tie' => false,
            'player_score' => 60,
            'opponent_score' => 75,
            'random_factor' => 0.1,
        ]);

        RaceResult::query()->create([
            'user_id' => $other->id,
            'attempt_type' => RaceAttemptType::Npc,
            'race_id' => $race->id,
            'won' => true,
            'is_tie' => false,
            'player_score' => 90,
            'opponent_score' => 50,
            'random_factor' => 0.1,
        ]);

        $response = $this->actingAs($user)->get(route('race-history.index'));

        $response->assertOk();
        $response->assertSeeInOrder(['60 vs 75', '80 vs 70']);
        $response->assertDontSee('90 vs 50', false);
    }
}
