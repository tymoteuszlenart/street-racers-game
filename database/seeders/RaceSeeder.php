<?php

namespace Database\Seeders;

use App\Models\Race;
use Illuminate\Database\Seeder;

class RaceSeeder extends Seeder
{
    public function run(): void
    {
        Race::query()->updateOrCreate(
            ['name' => 'Downtown Sprint'],
            [
                'description' => 'A short street race through downtown.',
                'unlock_level' => 1,
                'fuel_cost' => 10,
                'cash_reward_win' => 150,
                'cash_reward_loss' => 40,
                'reputation_reward_win' => 5,
                'reputation_reward_loss' => 1,
                'experience_reward_win' => 50,
                'experience_reward_loss' => 15,
                'opponent_power' => 35,
                'opponent_acceleration' => 30,
                'opponent_grip' => 28,
                'opponent_handling' => 22,
                'condition_damage_min' => 1,
                'condition_damage_max' => 3,
                'random_factor_variance' => 0.05,
                'active' => true,
            ],
        );
    }
}
