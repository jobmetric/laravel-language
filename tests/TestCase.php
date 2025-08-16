<?php

namespace JobMetric\Language\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JobMetric\Language\LanguageServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Random\RandomException;

class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            LanguageServiceProvider::class,
        ];
    }

    /**
     * @throws RandomException
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('app.timezone', 'UTC');

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        loadMigrationPath(__DIR__ . '/database/migrations');
    }
}
