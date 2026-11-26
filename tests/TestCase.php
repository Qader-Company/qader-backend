<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        /** @var Application $app */
        $app = parent::createApplication();

        $environment = $app->environment();
        $connection = (string) $app->make('config')->get('database.default');
        $driver = (string) $app->make('config')->get("database.connections.{$connection}.driver");
        $database = (string) $app->make('config')->get("database.connections.{$connection}.database");

        if ($environment !== 'testing' || $driver !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException(sprintf(
                'Unsafe test database configuration blocked: environment=%s, connection=%s, driver=%s, database=%s.',
                $environment,
                $connection,
                $driver,
                $database,
            ));
        }

        return $app;
    }
}
