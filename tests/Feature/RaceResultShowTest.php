<?php

namespace Tests\Feature;

use App\Enums\RaceAttemptType;
use App\Models\Race;
use App\Models\RaceResult;
use App\Models\User;
use App\Services\PlayerLevelService;
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
            'name' => 'Amateur',
            'fuel_cost' => 10,
            'cash_reward_win' => 200,
            'reputation_reward_win' => 12,
            'experience_reward_win' => 75,
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

    public function test_npc_race_result_page_hides_experience_at_max_level(): void
    {
        $user = User::factory()->create();
        $profile = $user->playerProfile()->firstOrFail();
        $levelService = app(PlayerLevelService::class);
        $profile->update([
            'level' => 100,
            'experience' => $levelService->maxExperience(),
            'fuel_current' => 100,
            'fuel_updated_at' => now(),
        ]);

        $race = Race::factory()->create([
            'name' => 'Amateur',
            'fuel_cost' => 10,
            'experience_reward_win' => 75,
        ]);

        $result = app(RaceService::class)
            ->withRandomUnit(fn (): float => 0.9)
            ->startNpcRace($user, $race, (string) Str::uuid());

        $this->actingAs($user)
            ->get(route('races.show', $result->raceResult))
            ->assertOk()
            ->assertSee(__('Rewards earned'), false)
            ->assertDontSee(__('XP'), false);
    }

    public function test_pvp_race_result_page_shows_cash_and_reputation_rewards(): void
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
                'rewards' => [
                    'cash' => 250,
                    'reputation' => 10,
                    'opponent_level' => 5,
                ],
            ],
            'random_factor' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('pvp.show', $raceResult))
            ->assertOk()
            ->assertSee(__('Rewards earned'), false)
            ->assertSee('+$250', false)
            ->assertSee('+10', false)
            ->assertDontSee(__('Experience'), false)
            ->assertSee(__('Score breakdown'), false)
            ->assertSee(__('Driver bonus'), false);
    }

    public function test_npc_race_result_shows_legacy_driver_level_bonus_in_breakdown(): void
    {
        $user = User::factory()->create();
        $race = Race::factory()->create();

        $raceResult = RaceResult::query()->create([
            'user_id' => $user->id,
            'attempt_type' => RaceAttemptType::Npc,
            'race_id' => $race->id,
            'pvp_race_id' => null,
            'won' => true,
            'is_tie' => false,
            'player_score' => 52.5,
            'opponent_score' => 40.5,
            'random_factor' => 0,
            'score_breakdown' => [
                'player' => [
                    'base' => 50.0,
                    'driver_level_bonus' => 2.5,
                    'random_adjustment' => 0,
                    'condition_penalty' => 0,
                ],
                'opponent' => [
                    'base' => 40.0,
                    'driver_level_bonus' => 0.5,
                    'random_adjustment' => 0,
                    'condition_penalty' => 0,
                ],
            ],
        ]);

        $this->actingAs($user)
            ->get(route('races.show', $raceResult))
            ->assertOk()
            ->assertSee(__('Driver bonus'), false)
            ->assertSee('2.5', false)
            ->assertSee('0.5', false);
    }
}
