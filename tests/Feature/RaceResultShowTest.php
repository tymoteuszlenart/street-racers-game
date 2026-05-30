<?php

namespace Tests\Feature;

use App\Enums\RaceAttemptType;
use App\Models\Race;
use App\Models\RaceResult;
use App\Models\User;
use App\Services\RaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RaceResultShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_npc_race_result_page_shows_rewards_after_win(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $profile->update(['fuel_current' => 100, 'fuel_updated_at' => now()]);

        $race = Race::factory()->create([
            'fuel_cost' => 10,
            'cash_reward_win' => 200,
            'reputation_reward_win' => 12,
            'experience_reward_win' => 75,
            'opponent_power' => 1,
            'opponent_acceleration' => 1,
            'opponent_grip' => 1,
            'opponent_handling' => 1,
        ]);

        $result = app(RaceService::class)
            ->withRandomUnit(fn (): float => 0.9)
            ->startNpcRace($user, $race, (string) Str::uuid());

        $this->actingAs($user)
            ->get(route('races.show', $result->raceResult))
            ->assertOk()
            ->assertSee(__('Rewards earned'), false)
            ->assertSee('+$200', false)
            ->assertSee('+12', false)
            ->assertSee('+75', false)
            ->assertSee(__('XP'), false)
            ->assertSee(__('Score breakdown'), false)
            ->assertSee(__('Driver stats used'), false);
    }

    public function test_pvp_race_result_page_does_not_show_npc_rewards(): void
    {
        $user = User::factory()->create();

        $raceResult = RaceResult::query()->create([
            'user_id' => $user->id,
            'attempt_type' => RaceAttemptType::Pvp,
            'race_id' => null,
            'pvp_race_id' => null,
            'won' => true,
            'is_tie' => false,
            'player_score' => 100,
            'opponent_score' => 50,
            'score_breakdown' => [
                'player' => [
                    'base' => 80.5,
                    'driver_bonus' => 1.8,
                    'driver_stats' => ['power' => 2, 'acceleration' => 2, 'grip' => 2, 'handling' => 2],
                    'random_adjustment' => 0.5,
                    'condition_penalty' => 0,
                ],
                'opponent' => [
                    'base' => 40.0,
                    'driver_bonus' => 0.45,
                    'driver_stats' => ['power' => 1, 'acceleration' => 1, 'grip' => 1, 'handling' => 1],
                    'random_adjustment' => 0,
                    'condition_penalty' => 0,
                ],
            ],
            'random_factor' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('pvp.show', $raceResult))
            ->assertOk()
            ->assertDontSee(__('Rewards earned'), false)
            ->assertSee(__('Score breakdown'), false)
            ->assertSee(__('Driver bonus'), false);
    }
}
