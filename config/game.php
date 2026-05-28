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

];
