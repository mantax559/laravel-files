<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Tests;

use Intervention\Image\Laravel\ServiceProvider as ImageServiceProvider;
use Mantax559\LaravelFiles\Providers\AppServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    private const string TESTBENCH_PACKAGE_NAME = 'mantax559-laravel-files-testbench';

    protected function defineEnvironment(mixed $app): void
    {
        tap($app['config'], static function ($config): void {
            $config->set('app.key', 'base64:'.base64_encode(hash('sha256', self::TESTBENCH_PACKAGE_NAME, true)));
            $config->set('app.timezone', 'UTC');
            $config->set('app.locale', 'en');
            $config->set('app.fallback_locale', 'en');
            $config->set('cache.default', 'array');
            $config->set('filesystems.default', 'local');
            $config->set('queue.default', 'sync');
            $config->set('session.driver', 'array');
        });
    }

    protected function getPackageProviders(mixed $app): array
    {
        return [
            ImageServiceProvider::class,
            AppServiceProvider::class,
        ];
    }
}
