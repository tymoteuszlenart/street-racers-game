<?php

namespace Tests;

use Database\Seeders\CarModelSeeder;
use Database\Seeders\PartModelSeeder;
use Database\Seeders\RaceSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected bool $seedCarModels = true;

    protected bool $seedRaces = true;

    protected bool $seedPartModels = true;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->seedCarModels && Schema::hasTable('car_models')) {
            $this->seed(CarModelSeeder::class);
        }

        if ($this->seedRaces && Schema::hasTable('races')) {
            $this->seed(RaceSeeder::class);
        }

        if ($this->seedPartModels && Schema::hasTable('part_models')) {
            $this->seed(PartModelSeeder::class);
        }
    }
}
