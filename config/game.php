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

    /*
   * NPC opponents scale to a per-level "expected" build (anchor), not the player's exact car.
   * Beating the anchor through tuning/gear raises win odds; tier multipliers set Amateur / Semi-Pro / Pro difficulty.
   * Opponents use their own luck roll so close matchups are not deterministic when stats are similar.
   */
    'npc_races' => [
        'anchor' => [
            'car' => [
                'power' => 44,
                'acceleration' => 49,
                'grip' => 46,
                'handling' => 48,
            ],
            'car_per_level' => [
                'power' => 2.0,
                'acceleration' => 2.0,
                'grip' => 1.5,
                'handling' => 1.5,
            ],
            'driver_stat_per_level' => 0.75,
        ],
        'tuning_blend' => [
            'max' => 0.85,
            'slope' => 3.2,
        ],
        'tiers' => [
            'Amateur' => ['strength_multiplier' => 0.87],
            'Semi-Pro' => ['strength_multiplier' => 0.975],
            'Pro' => ['strength_multiplier' => 0.995],
        ],
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
        'driver_stats' => [
            'base' => [
                'power' => 1,
                'acceleration' => 1,
                'grip' => 1,
                'handling' => 1,
            ],
            'points_per_level' => 3,
            'race_weights' => [
                'power' => 0.15,
                'acceleration' => 0.12,
                'grip' => 0.10,
                'handling' => 0.08,
            ],
            'labels' => [
                'power' => 'Force',
                'acceleration' => 'Reaction',
                'grip' => 'Control',
                'handling' => 'Technique',
            ],
        ],
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

    'shop' => [
        'paid_premium_fuel_max' => 20,
        'checkout_rate_limit_per_minute' => 10,
        'products' => [
            'fuel-add-50' => [
                'slug' => 'fuel-add-50',
                'type' => 'regular_fuel',
                'name' => 'Fuel +50',
                'description' => 'Add 50 regular fuel instantly (up to your tank max).',
                'price_cents' => 299,
                'grant_mode' => 'add',
                'grant_amount' => 50,
                'sort_order' => 10,
                'active' => true,
            ],
            'fuel-fill-max' => [
                'slug' => 'fuel-fill-max',
                'type' => 'regular_fuel',
                'name' => 'Fill Fuel Tank',
                'description' => 'Refill regular fuel to your current maximum.',
                'price_cents' => 499,
                'grant_mode' => 'fill_to_max',
                'grant_amount' => null,
                'sort_order' => 20,
                'active' => true,
            ],
            'premium-fuel-5' => [
                'slug' => 'premium-fuel-5',
                'type' => 'premium_fuel',
                'name' => 'Premium Fuel (5)',
                'description' => 'Five premium fuel for club tournaments. Raises paid storage cap to 20.',
                'price_cents' => 499,
                'grant_amount' => 5,
                'sort_order' => 30,
                'active' => true,
            ],
            'premium-fuel-10' => [
                'slug' => 'premium-fuel-10',
                'type' => 'premium_fuel',
                'name' => 'Premium Fuel (10)',
                'description' => 'Ten premium fuel for club tournaments. Raises paid storage cap to 20.',
                'price_cents' => 899,
                'grant_amount' => 10,
                'sort_order' => 40,
                'active' => true,
            ],
        ],
    ],

    'condition' => [
        'car_max' => 999,
        'part_max' => 200,
        'tiers' => [
            'good_min_percent' => 70,
            'worn_min_percent' => 40,
            'critical_max_percent' => 10,
        ],
        'penalties' => [
            'worn' => 5,
            'damaged' => 8,
            'critical' => 12,
        ],
        'ui' => [
            'green' => '#10b981',
            'yellow' => '#facc15',
            'red' => '#ef4444',
        ],
    ],

    'mechanic' => [
        'unlock_level' => 5,
        'max_upgrade_level' => 9,
        'bonus_percent_per_level' => 10,
        'upgrade_cost_percent_of_price_per_level' => 15,
        'repair' => [
            'car_base_cost' => 50,
            'part_base_cost' => 25,
            'cost_per_missing_condition_point' => 2,
        ],
    ],

    'sell' => [
        'car_refund_percent' => 65,
        'part_refund_percent' => 65,
        'bundled_part_refund_percent' => 70,
        'reward_car_refund_percent' => 80,
        'reward_part_refund_percent' => 80,
        'reward_bundled_part_refund_percent' => 80,
        'min_refund' => 1,
    ],

    'open_cup' => [
        'unlock_level' => 5,
        'join_window_minutes' => 45,
        'settling_minutes' => 3,
        'entry_fee_cash' => 2000,
        'max_entrants' => 8,
        'solo_npc_races' => 3,
        'participation_cash' => 1200,
        'participation_cups' => 1,
        'bracket_win_cups' => 3,
        'champion_pot_share' => 0.40,
        'random_factor_variance' => 0.03,
        'condition_damage_min' => 1,
        'condition_damage_max' => 3,
        'npc_strength_multiplier' => 0.92,
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
