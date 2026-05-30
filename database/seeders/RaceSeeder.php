<?php

namespace Database\Seeders;

use App\Models\Race;
use Illuminate\Database\Seeder;

class RaceSeeder extends Seeder
{
    public function run(): void
    {
        Race::query()->where('name', 'Downtown Sprint')->delete();

        $tiers = [
            'Amateur' => [
                'description' => 'A forgiving street run — good for learning the race loop.',
                'unlock_level' => 1,
                'fuel_cost' => 8,
                'cash_reward_win' => 100,
                'cash_reward_loss' => 25,
                'reputation_reward_win' => 3,
                'reputation_reward_loss' => 1,
                'experience_reward_win' => 40,
                'experience_reward_loss' => 10,
                'opponent_power' => 22,
                'opponent_acceleration' => 20,
                'opponent_grip' => 18,
                'opponent_handling' => 15,
                'opponent_stat_power' => 1,
                'opponent_stat_acceleration' => 1,
                'opponent_stat_grip' => 1,
                'opponent_stat_handling' => 1,
            ],
            'Semi-Pro' => [
                'description' => 'A tougher opponent with better car stats and driver skill.',
                'unlock_level' => 1,
                'fuel_cost' => 12,
                'cash_reward_win' => 150,
                'cash_reward_loss' => 40,
                'reputation_reward_win' => 5,
                'reputation_reward_loss' => 1,
                'experience_reward_win' => 50,
                'experience_reward_loss' => 15,
                'opponent_power' => 30,
                'opponent_acceleration' => 26,
                'opponent_grip' => 24,
                'opponent_handling' => 20,
                'opponent_stat_power' => 3,
                'opponent_stat_acceleration' => 3,
                'opponent_stat_grip' => 3,
                'opponent_stat_handling' => 3,
            ],
            'Pro' => [
                'description' => 'The hardest NPC on the list — elite car stats and driver skill.',
                'unlock_level' => 1,
                'fuel_cost' => 15,
                'cash_reward_win' => 220,
                'cash_reward_loss' => 55,
                'reputation_reward_win' => 8,
                'reputation_reward_loss' => 2,
                'experience_reward_win' => 65,
                'experience_reward_loss' => 20,
                'opponent_power' => 35,
                'opponent_acceleration' => 30,
                'opponent_grip' => 28,
                'opponent_handling' => 22,
                'opponent_stat_power' => 6,
                'opponent_stat_acceleration' => 5,
                'opponent_stat_grip' => 5,
                'opponent_stat_handling' => 4,
            ],
        ];

        foreach ($tiers as $name => $attributes) {
            Race::query()->updateOrCreate(
                ['name' => $name],
                [
                    ...$attributes,
                    'condition_damage_min' => 1,
                    'condition_damage_max' => 3,
                    'random_factor_variance' => 0.05,
                    'active' => true,
                ],
            );
        }
    }
}
