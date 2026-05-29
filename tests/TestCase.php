<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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
            $config->set('laravel-observability.log_table', 'logs');
            $config->set('laravel-observability.user_model', 'Mantax559\\LaravelFiles\\Models\\File');
            $config->set('laravel-observability.actual_user_column', 'actual_user_id');
            $config->set('laravel-observability.assigned_user_column', 'assigned_user_id');
            $config->set('laravel-observability.code_length', 6);
        });
    }

    protected function getPackageProviders(mixed $app): array
    {
        return [
            ImageServiceProvider::class,
            AppServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (Schema::hasTable(config('laravel-observability.log_table'))) {
            return;
        }

        Schema::create(config('laravel-observability.log_table'), function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('file_id')->nullable();
            $table->string(config('laravel-observability.actual_user_column'))->nullable();
            $table->string(config('laravel-observability.assigned_user_column'))->nullable();
            $table->string('status');
            $table->string('code');
            $table->longText('message');
            $table->json('details')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('locale')->nullable();
            $table->string('request_method')->nullable();
            $table->text('request_url')->nullable();
            $table->text('referer')->nullable();
            $table->string('session_id')->nullable();
            $table->string('route_action')->nullable();
            $table->integer('response_status')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }
}
