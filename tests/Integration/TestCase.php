<?php

namespace Tests\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase as BaseTestCase;

/**
 * Base class for MySQL-backed integration tests (locking, concurrency, idempotency).
 *
 * Run with: php artisan test --configuration=phpunit.mysql.xml
 * Requires a running MySQL database (see AGENTS.md and .env.testing.example).
 */
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'mysql') {
            $this->fail(
                'Integration tests require MySQL. Run: php artisan test --configuration=phpunit.mysql.xml'
            );
        }
    }
}
