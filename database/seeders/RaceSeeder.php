<?php

namespace Database\Seeders;

use App\Enums\RaceTier;
use App\Enums\RaceType;
use App\Models\Race;
use Illuminate\Database\Seeder;

class RaceSeeder extends Seeder
{
    /**
     * @return array<string, array<string, mixed>>
     */
    private function tierTemplates(): array
    {
        return [
            RaceTier::Amateur->value => [
                'description' => 'A forgiving run — good for learning this race type.',
                'unlock_level' => 1,
                'fuel_cost' => 8,
                'cash_reward_win' => 100,
                'cash_reward_loss' => 25,
                'reputation_reward_win' => 3,
                'reputation_reward_loss' => 1,
                'experience_reward_win' => 40,
                'experience_reward_loss' => 10,
                'random_factor_variance' => 0.05,
            ],
            RaceTier::SemiPro->value => [
                'description' => 'A tougher opponent with better car stats and driver skill.',
                'unlock_level' => 1,
                'fuel_cost' => 12,
                'cash_reward_win' => 150,
                'cash_reward_loss' => 40,
                'reputation_reward_win' => 5,
                'reputation_reward_loss' => 1,
                'experience_reward_win' => 50,
                'experience_reward_loss' => 15,
                'random_factor_variance' => 0.05,
            ],
            RaceTier::Pro->value => [
                'description' => 'The hardest tier on this track — elite car stats and driver skill.',
                'unlock_level' => 1,
                'fuel_cost' => 15,
                'cash_reward_win' => 220,
                'cash_reward_loss' => 55,
                'reputation_reward_win' => 8,
                'reputation_reward_loss' => 2,
                'experience_reward_win' => 65,
                'experience_reward_loss' => 20,
                'random_factor_variance' => 0.08,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function typeDescriptions(): array
    {
        return [
            RaceType::Drag->value => 'Straight-line power — Force and Reaction are key.',
            RaceType::Sprint->value => 'Short bursts — Reaction and acceleration win races.',
            RaceType::Drift->value => 'Technical corners — Control and Technique matter most.',
            RaceType::Circuit->value => 'Balanced track — all driver stats contribute.',
        ];
    }

    public function run(): void
    {
        Race::query()->where('name', 'Downtown Sprint')->delete();
        Race::query()->whereIn('name', ['Amateur', 'Semi-Pro', 'Pro'])->delete();

        foreach (RaceType::cases() as $raceType) {
            foreach ($this->tierTemplates() as $tierValue => $tierAttributes) {
                $tier = RaceTier::from($tierValue);

                Race::query()->updateOrCreate(
                    [
                        'race_type' => $raceType,
                        'race_tier' => $tier,
                    ],
                    [
                        'name' => $raceType->label().' '.$tier->label(),
                        'description' => trim($this->typeDescriptions()[$raceType->value].' '.$tierAttributes['description']),
                        'unlock_level' => $tierAttributes['unlock_level'],
                        'fuel_cost' => $tierAttributes['fuel_cost'],
                        'cash_reward_win' => $tierAttributes['cash_reward_win'],
                        'cash_reward_loss' => $tierAttributes['cash_reward_loss'],
                        'reputation_reward_win' => $tierAttributes['reputation_reward_win'],
                        'reputation_reward_loss' => $tierAttributes['reputation_reward_loss'],
                        'experience_reward_win' => $tierAttributes['experience_reward_win'],
                        'experience_reward_loss' => $tierAttributes['experience_reward_loss'],
                        'opponent_power' => 0,
                        'opponent_acceleration' => 0,
                        'opponent_grip' => 0,
                        'opponent_handling' => 0,
                        'opponent_stat_power' => 0,
                        'opponent_stat_acceleration' => 0,
                        'opponent_stat_grip' => 0,
                        'opponent_stat_handling' => 0,
                        'random_factor_variance' => $tierAttributes['random_factor_variance'],
                        'condition_damage_min' => 1,
                        'condition_damage_max' => 3,
                        'active' => true,
                    ],
                );
            }
        }
    }
}
