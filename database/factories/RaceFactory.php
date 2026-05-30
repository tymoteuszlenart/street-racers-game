<?php

namespace Database\Factories;

use App\Models\Race;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Race>
 */
class RaceFactory extends Factory
{
    protected $model = Race::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'unlock_level' => 1,
            'fuel_cost' => 10,
            'cash_reward_win' => 100,
            'cash_reward_loss' => 25,
            'reputation_reward_win' => 5,
            'reputation_reward_loss' => 1,
            'experience_reward_win' => 50,
            'experience_reward_loss' => 10,
            'opponent_power' => 40,
            'opponent_acceleration' => 35,
            'opponent_grip' => 30,
            'opponent_handling' => 25,
            'opponent_stat_power' => 1,
            'opponent_stat_acceleration' => 1,
            'opponent_stat_grip' => 1,
            'opponent_stat_handling' => 1,
            'condition_damage_min' => 1,
            'condition_damage_max' => 3,
            'random_factor_variance' => 0.05,
            'active' => true,
        ];
    }
}
