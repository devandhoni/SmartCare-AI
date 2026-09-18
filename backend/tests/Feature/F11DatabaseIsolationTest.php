<?php

namespace Tests\Feature;

use Tests\TestCase;

class F11DatabaseIsolationTest extends TestCase
{
    public function test_f11_uses_an_in_memory_sqlite_database(): void
    {
        $this->assertSame('testing', app()->environment());

        $this->assertSame(
            'sqlite',
            config('database.default')
        );

        $this->assertSame(
            ':memory:',
            config('database.connections.sqlite.database')
        );
    }
}