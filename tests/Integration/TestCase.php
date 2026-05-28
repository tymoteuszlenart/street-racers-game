<?php

namespace Tests\Integration;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase as BaseTestCase;

/**
 * Base class for MySQL-backed integration tests (locking, concurrency, idempotency).
 *
 * Run with: composer test:integration
 * Requires a running MySQL database (see AGENTS.md and .env.testing.example).
 */
abstract class TestCase extends BaseTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        if (! $this->app) {
            $this->refreshApplication();
        }

        $this->assertIntegrationTestDatabaseConfigured();

        parent::setUp();
    }

    protected function assertIntegrationTestDatabaseConfigured(): void
    {
        if (config('database.default') !== 'mysql') {
            $this->fail(
                'Integration tests require MySQL. Run: composer test:integration'
            );
        }

        $database = config('database.connections.mysql.database');

        if ($database !== 'street_racers_test') {
            $this->fail(
                'Integration tests must use the dedicated test database street_racers_test '
                ."(configured: {$database}). See phpunit.mysql.xml and AGENTS.md."
            );
        }
    }
}
