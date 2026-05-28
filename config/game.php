<?php

return [

    'fuel' => [
        'regeneration_minutes' => 5,
        'regeneration_amount' => 1,
        'default_max' => 100,
    ],

    'race' => [
        'idempotency_ttl_hours' => 24,
        'start_rate_limit_per_minute' => 30,
    ],

    'pvp' => [
        'daily_pair_cap' => 10,
        'fuel_cost' => 10,
        'condition_damage_min' => 1,
        'condition_damage_max' => 3,
        'random_factor_variance' => 0.05,
    ],

    'player' => [
        'max_level' => 50,
        'experience_per_level' => 100,
    ],

    'daily_rewards' => [
        'login' => [
            'fuel' => 20,
        ],
    ],

    'clubs' => [
        'unlock_level' => 10,
        'max_members' => (int) env('GAME_CLUBS_MAX_MEMBERS', 30),
        'name_min_length' => 3,
        'name_max_length' => 64,
    ],

    'premium_fuel' => [
        'default_max' => 5,
        'daily_claim_amount' => 1,
        'tournament_entry_cost' => 1,
    ],

    'tournaments' => [
        'unlock_level' => 15,
        'max_attempts_per_player' => 20,
        'counted_attempts_per_player' => 10,
        'random_factor_variance' => 0.03,
        'condition_damage_min' => 2,
        'condition_damage_max' => 5,
        'points_win' => 3,
        'points_loss' => 1,
        'points_perfect_bonus' => 2,
        'perfect_win_margin' => 15,
        'opponent' => [
            'power' => 80,
            'acceleration' => 80,
            'grip' => 80,
            'handling' => 80,
        ],
        'weekly_rewards' => [
            1 => ['cash' => 5000, 'premium_fuel' => 2],
            2 => ['cash' => 3000, 'premium_fuel' => 1],
            3 => ['cash' => 1500, 'premium_fuel' => 1],
        ],
        'weekly_reward_top_clubs' => 3,
    ],

];
