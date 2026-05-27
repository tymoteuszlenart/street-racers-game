<?php

namespace Tests\Integration;

use Illuminate\Support\Facades\DB;

class MysqlConnectionTest extends TestCase
{
    public function test_it_uses_the_mysql_connection(): void
    {
        $this->assertSame('mysql', config('database.default'));
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertNotEmpty(config('database.connections.mysql.database'));
    }
}
