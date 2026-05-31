<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Tests\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Mantax559\LaravelFiles\Enums\FileExtension;
use Mantax559\LaravelFiles\Helpers\FileHelper;
use Mantax559\LaravelFiles\Models\File;
use Mantax559\LaravelFiles\Tests\Support\FakeImage;
use Mantax559\LaravelFiles\Tests\TestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;

final class FileHelperTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        config([
            'app.url' => 'https://example.com',
            'laravel-files.disk' => 'local',
            'laravel-files.image_cache_disk' => 'public',
            'laravel-files.image_cache_quality' => 90,
            'laravel-files.image_cache_sizes' => [
                'thumbnail' => ['width' => 10, 'height' => 20],
                'auto' => [],
            ],
        ]);
    }

    #[Test]
    public function cache_image_helper_uses_configured_size_and_model_folder(): void
    {
        Storage::disk('local')->put('image/files/file-id/source.jpg', 'image');
        $this->mockImageFacade()
            ->shouldReceive('decodePath')
            ->once()
            ->andReturn(new FakeImage(encodedContents: 'cached'));
        $file = new File([
            'folder' => 'image/files/file-id',
            'extension' => FileExtension::Jpg,
            'size' => 5,
        ]);
        $file->id = 'source';

        cache_image($file, 'thumbnail');

        $this->assertTrue(Storage::disk('public')->exists('cache/image/files/file-id/source/10x20.avif'));
    }

    #[Test]
    public function cache_image_helper_uses_original_size_for_invalid_named_size(): void
    {
        Storage::disk('local')->put('image/products/source.jpg', 'image');
        $this->mockImageFacade()
            ->shouldReceive('decodePath')
            ->once()
            ->andReturn(new FakeImage(30, 40, 'cached'));

        cache_image('image/products/source.jpg', 'auto');

        $this->assertTrue(Storage::disk('public')->exists('cache/image/products/source/autoxauto.avif'));
    }

    #[Test]
    public function helpers_file_can_be_loaded_when_functions_already_exist(): void
    {
        $this->assertTrue(function_exists('cache_image'));
        $this->assertTrue(function_exists('email_image'));

        require dirname(__DIR__, 2).'/src/helpers.php';

        $this->assertTrue(function_exists('cache_image'));
        $this->assertTrue(function_exists('email_image'));
    }

    #[Test]
    public function email_image_returns_cache_url_for_public_application_url(): void
    {
        Storage::disk('local')->put('image/products/source.jpg', 'image');
        $this->mockImageFacade()
            ->shouldReceive('decodePath')
            ->once()
            ->andReturn(new FakeImage(encodedContents: 'cached'));
        $message = Mockery::mock(Message::class);
        $message->shouldNotReceive('embed');

        $this->assertStringContainsString(
            'cache/image/products/source/10x20.avif',
            FileHelper::emailImage('image/products/source.jpg', 'thumbnail', $message)
        );
    }

    #[Test]
    public function email_image_helper_returns_cache_url_for_public_application_url(): void
    {
        Storage::disk('local')->put('image/products/source.jpg', 'image');
        $this->mockImageFacade()
            ->shouldReceive('decodePath')
            ->once()
            ->andReturn(new FakeImage(encodedContents: 'cached'));
        $message = Mockery::mock(Message::class);
        $message->shouldNotReceive('embed');

        $this->assertStringContainsString(
            'cache/image/products/source/10x20.avif',
            email_image('image/products/source.jpg', 'thumbnail', $message)
        );
    }

    #[Test]
    public function email_image_returns_cache_url_when_application_url_has_no_host(): void
    {
        config(['app.url' => 'invalid-url']);
        Storage::disk('local')->put('image/products/source.jpg', 'image');
        $this->mockImageFacade()
            ->shouldReceive('decodePath')
            ->once()
            ->andReturn(new FakeImage(encodedContents: 'cached'));
        $message = Mockery::mock(Message::class);
        $message->shouldNotReceive('embed');

        $this->assertStringContainsString(
            'cache/image/products/source/10x20.avif',
            FileHelper::emailImage('image/products/source.jpg', 'thumbnail', $message)
        );
    }

    #[Test]
    public function email_image_embeds_cache_file_for_localhost_application_url(): void
    {
        config(['app.url' => 'http://localhost']);
        Storage::disk('local')->put('image/products/source.jpg', 'image');
        $this->mockImageFacade()
            ->shouldReceive('decodePath')
            ->once()
            ->andReturn(new FakeImage(encodedContents: 'cached'));
        $message = Mockery::mock(Message::class);
        $message->shouldReceive('embed')->once()->andReturn('embedded-image');

        $this->assertSame('embedded-image', FileHelper::emailImage('image/products/source.jpg', 'thumbnail', $message));
    }

    private function mockImageFacade(): MockInterface
    {
        $mock = Mockery::mock();
        Image::swap($mock);

        return $mock;
    }
}
