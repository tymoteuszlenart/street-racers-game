<?php

namespace Tests;

use Database\Seeders\CarModelSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected bool $seedCarModels = true;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->seedCarModels && Schema::hasTable('car_models')) {
            $this->seed(CarModelSeeder::class);
        }
    }
}
