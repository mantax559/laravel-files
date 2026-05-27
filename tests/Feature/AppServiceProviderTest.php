<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Tests\Feature;

use Illuminate\Support\Facades\File;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Vips\Driver;
use Mantax559\LaravelFiles\Providers\AppServiceProvider;
use Mantax559\LaravelFiles\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class AppServiceProviderTest extends TestCase
{
    #[Test]
    public function vendor_publish_writes_config_stub_to_application(): void
    {
        $published = config_path('laravel-files.php');

        try {
            $this->artisan('vendor:publish', [
                '--provider' => AppServiceProvider::class,
                '--tag' => 'config',
                '--force' => true,
            ]);

            $this->assertFileExists($published);
            $this->assertNotEmpty(File::get($published));
        } finally {
            if (File::exists($published)) {
                File::delete($published);
            }
        }
    }

    #[Test]
    public function provider_registers_vips_driver_when_image_driver_is_missing(): void
    {
        config(['image.driver' => null]);

        $provider = new AppServiceProvider($this->app);
        $provider->register();

        $this->assertSame(Driver::class, config('image.driver'));
    }

    #[Test]
    public function provider_overwrites_existing_image_driver_with_vips_default(): void
    {
        config()->set('image.driver', GdDriver::class);

        $provider = new AppServiceProvider($this->app);
        $provider->register();

        $this->assertSame(Driver::class, config('image.driver'));
    }
}
