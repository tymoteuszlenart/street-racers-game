<?php

namespace Tests\Integration;

use Illuminate\Support\Facades\DB;

class MysqlConnectionTest extends TestCase
{
    public function test_it_uses_the_mysql_connection(): void
    {
        $this->assertSame('mysql', config('database.default'));
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertSame(
            'street_racers_test',
            config('database.connections.mysql.database'),
            'Integration tests must use the dedicated test database (see phpunit.mysql.xml).'
        );

        $this->assertSame(1, DB::selectOne('SELECT 1 AS ok')->ok);
    }
}
