<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Providers;

use Illuminate\Support\ServiceProvider;
use Intervention\Image\Drivers\Vips\Driver;

final class AppServiceProvider extends ServiceProvider
{
    private const string PATH_CONFIG = __DIR__.'/../../config/laravel-files.php';

    private const string PATH_MIGRATIONS = __DIR__.'/../../database/migrations';

    private const string PATH_LANG = __DIR__.'/../../resources/lang';

    public function boot(): void
    {
        $this->publishes([
            self::PATH_CONFIG => config_path('laravel-files.php'),
        ], 'config');

        $this->loadMigrationsFrom(self::PATH_MIGRATIONS);
        $this->loadJsonTranslationsFrom(self::PATH_LANG);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(self::PATH_CONFIG, 'laravel-files');
        $this->app['config']->set('image.driver', Driver::class);
    }
}
