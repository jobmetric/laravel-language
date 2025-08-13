<?php

namespace JobMetric\Language\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JobMetric\Language\LanguageServiceProvider;
use JobMetric\Metadata\MetadataServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            LanguageServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
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

        //loadMigrationPath(__DIR__ . '/database/migrations');
    }
}
