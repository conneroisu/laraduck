<?php

namespace Laraduck\EloquentDuckDB\Tests;

use Laraduck\EloquentDuckDB\DuckDBServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            DuckDBServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'duckdb');
        $app['config']->set('database.connections.duckdb', [
            'driver' => 'duckdb',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}