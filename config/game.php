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

];
