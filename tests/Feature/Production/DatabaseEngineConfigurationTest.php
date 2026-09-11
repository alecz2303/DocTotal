<?php

namespace Tests\Feature\Production;

use Tests\TestCase;

class DatabaseEngineConfigurationTest extends TestCase
{
    public function test_mysql_connection_explicitly_uses_innodb(): void
    {
        $this->assertSame(
            'InnoDB',
            config('database.connections.mysql.engine')
        );
    }
}
