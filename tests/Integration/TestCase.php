<?php

namespace Tests\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase as BaseTestCase;

/**
 * Base class for MySQL-backed integration tests (locking, concurrency, idempotency).
 *
 * Requires a running MySQL database; configure DB_* in phpunit.xml or .env.testing.
 */
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (config('database.default') !== 'mysql') {
            config([
                'database.default' => 'mysql',
            ]);
        }

        parent::setUp();
    }
}
