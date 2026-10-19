<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication {
        createApplication as createApplicationFromTrait;
    }

    /**
     * Never touch the real database: stop before any test (and its transactions) when the
     * connection isn't a "*_testing" database (see DB_DATABASE in phpunit.xml).
     */
    public function createApplication()
    {
        $app = $this->createApplicationFromTrait();

        $connection = $app['config']->get('database.default');
        $database = (string) $app['config']->get("database.connections.{$connection}.database");
        if ($connection !== 'sqlite' && !str_ends_with($database, '_testing')) {
            throw new \RuntimeException("Tests must run on a *_testing database, not \"{$database}\". Check DB_DATABASE in phpunit.xml.");
        }

        return $app;
    }
}
