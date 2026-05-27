<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Tests\Integration;

use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Mantax559\LaravelFiles\Enums\FileExtension;
use Mantax559\LaravelFiles\Enums\FileSource;
use Mantax559\LaravelFiles\Models\File;
use Mantax559\LaravelFiles\Services\FileService;
use Mantax559\LaravelFiles\Tests\Support\FakeImage;
use Mantax559\LaravelFiles\Tests\TestCase;
use Mantax559\LaravelHelpers\Exceptions\UserFriendlyException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class FileServiceTest extends TestCase
{
    private const string PNG_CONTENTS = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        config([
            'laravel-files.accept_archive_extensions' => [FileExtension::Zip],
            'laravel-files.accept_audio_extensions' => [FileExtension::Mp3],
            'laravel-files.accept_document_extensions' => [FileExtension::Pdf, FileExtension::Txt],
            'laravel-files.accept_image_extensions' => [FileExtension::Avif, FileExtension::Jpg, FileExtension::Png, FileExtension::Webp],
            'laravel-files.accept_video_extensions' => [FileExtension::Mp4],
            'laravel-files.accept_file_extensions' => [FileExtension::Json],
            'laravel-files.disk' => 'local',
            'laravel-files.image_cache_disk' => 'public',
            'laravel-files.image_cache_quality' => 90,
            'laravel-files.max_file_size_bytes' => 1024 * 1024,
            'laravel-files.max_image_side_pixels' => 2048,
            'laravel-files.max_upload_file_size_bytes' => 1024 * 1024,
            'laravel-files.max_upload_image_side_pixels' => 8192,
        ]);
    }

    #[Test]
    public function save_converts_supported_images_to_avif(): void
    {
        $this->mockImageFacade()
            ->shouldReceive('decodeBinary')
            ->once()
            ->andReturn(new FakeImage(encodedContents: 'stored-image'));

        $path = (new FileService)->save($this->temporaryFile(base64_decode(self::PNG_CONTENTS), 'png'), 'Products');

        $this->assertStringStartsWith('image/products/', $path);
        $this->assertStringEndsWith('.avif', $path);
        Storage::disk('local')->assertExists($path);
        $this->assertSame('stored-image-avif-90', Storage::disk('local')->get($path));
    }

    #[Test]
    public function save_keeps_non_convertible_files_in_their_folder(): void
    {
        $path = (new FileService)->save($this->temporaryFile('%PDF-1.4', 'pdf'), 'Invoices');

        $this->assertStringStartsWith('document/invoices/', $path);
        $this->assertStringEndsWith('.pdf', $path);
        $this->assertSame('%PDF-1.4', Storage::disk('local')->get($path));
    }

    #[Test]
    public function save_uses_mime_type_when_extension_is_missing(): void
    {
        $path = (new FileService)->save($this->temporaryFile('hello', ''), 'Texts');

        $this->assertStringStartsWith('document/texts/', $path);
        $this->assertStringEndsWith('.txt', $path);
    }

    #[Test]
    public function save_rejects_unreadable_paths_and_unsupported_extensions(): void
    {
        $this->expectException(UserFriendlyException::class);
        $this->expectExceptionMessage('The file could not be read');

        (new FileService)->save('missing-file.pdf', 'Files');
    }

    #[Test]
    public function save_rejects_extensions_that_are_not_configured_for_their_folder(): void
    {
        config(['laravel-files.accept_document_extensions' => [FileExtension::Txt]]);

        $this->expectException(UserFriendlyException::class);
        $this->expectExceptionMessage('pdf file format is not allowed');

        (new FileService)->save($this->temporaryFile('%PDF-1.4', 'pdf'), 'Invoices');
    }

    #[Test]
    public function save_accepts_string_extensions_from_config(): void
    {
        config(['laravel-files.accept_document_extensions' => ['pdf']]);

        $path = (new FileService)->save($this->temporaryFile('%PDF-1.4', 'pdf'), 'Invoices');

        $this->assertStringEndsWith('.pdf', $path);
    }

    #[Test]
    public function save_reports_storage_and_upload_size_limits(): void
    {
        config(['laravel-files.max_upload_file_size_bytes' => 4]);

        try {
            (new FileService)->save($this->temporaryFile('12345', 'pdf'), 'Invoices');
            $this->fail('Expected upload size exception.');
        } catch (UserFriendlyException $exception) {
            $this->assertStringContainsString('uploaded file is too large', $exception->getMessage());
        }

        config([
            'laravel-files.max_upload_file_size_bytes' => 1024,
            'laravel-files.max_file_size_bytes' => 4,
        ]);

        $this->expectException(UserFriendlyException::class);
        $this->expectExceptionMessage('stored file is too large');

        (new FileService)->save($this->temporaryFile('12345', 'pdf'), 'Invoices');
    }

    #[Test]
    public function seeder_source_deletes_target_folder_only_once(): void
    {
        $service = new FileService(FileSource::Seeder);

        $firstPath = $service->save($this->temporaryFile('%PDF-1.4', 'pdf'), 'Invoices');
        $secondPath = $service->save($this->temporaryFile('%PDF-1.4', 'pdf'), 'Invoices');

        Storage::disk('local')->assertExists($firstPath);
        Storage::disk('local')->assertExists($secondPath);
        $this->assertStringStartsWith('seeder/document/invoices/', $firstPath);
    }

    #[Test]
    public function cache_image_creates_cache_file_and_reuses_existing_cache(): void
    {
        Storage::disk('local')->put('image/products/source.jpg', 'image');
        $this->mockImageFacade()
            ->shouldReceive('decodePath')
            ->once()
            ->andReturn(new FakeImage(encodedContents: 'cached'));

        $service = new FileService;
        $firstUrl = $service->cacheImage('image/products/source.jpg', 10, 20, 'Products');
        $secondUrl = $service->cacheImage('image/products/source.jpg', 10, 20, 'Products');

        Storage::disk('public')->assertExists('cache/image/products/source-10x20.jpg');
        $this->assertSame($firstUrl, $secondUrl);
    }

    #[Test]
    public function cache_images_maps_all_sizes(): void
    {
        Storage::disk('local')->put('image/products/source.jpg', 'image');
        $this->mockImageFacade()
            ->shouldReceive('decodePath')
            ->twice()
            ->andReturn(new FakeImage(encodedContents: 'cached'));

        $urls = (new FileService)->cacheImages('image/products/source.jpg', [
            ['width' => 10, 'height' => 20],
            ['width' => 30, 'height' => 40],
        ], 'Products');

        $this->assertCount(2, $urls);
        Storage::disk('public')->assertExists('cache/image/products/source-10x20.jpg');
        Storage::disk('public')->assertExists('cache/image/products/source-30x40.jpg');
    }

    #[Test]
    public function cache_image_fails_when_source_does_not_exist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File does not exist');

        (new FileService)->cacheImage('missing.jpg', 10, 20);
    }

    #[Test]
    public function rollback_deletes_uploaded_files(): void
    {
        $service = new FileService;
        $path = $service->save($this->temporaryFile('%PDF-1.4', 'pdf'), 'Invoices');

        $this->assertSame(1, $service->rollbackFiles());
        Storage::disk('local')->assertMissing($path);
    }

    #[Test]
    public function private_delete_handles_empty_missing_model_cache_and_restore(): void
    {
        $service = new FileService;
        $delete = new ReflectionMethod(FileService::class, 'delete');

        $this->assertFalse($delete->invoke($service, null));
        $this->assertFalse($delete->invoke($service, 'missing.pdf'));

        Storage::disk('local')->put('document/invoices/file.pdf', 'contents');
        Storage::disk('public')->put('cache/image/123/thumb.jpg', 'cache');

        $model = new File;
        $model->setAttribute($model->getKeyName(), '123');

        $this->assertTrue($delete->invoke($service, 'document/invoices/file.pdf', $model));
        Storage::disk('local')->assertMissing('document/invoices/file.pdf');
        Storage::disk('public')->assertMissing('cache/image/123/thumb.jpg');

        $this->assertSame(1, $service->rollbackFiles());
        Storage::disk('local')->assertExists('document/invoices/file.pdf');
        $this->assertSame('contents', Storage::disk('local')->get('document/invoices/file.pdf'));
    }

    #[Test]
    public function transaction_commits_successful_callback(): void
    {
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        $called = false;

        FileService::transactionWithFileRollback(function () use (&$called): void {
            $called = true;
        }, new FileService);

        $this->assertTrue($called);
    }

    #[Test]
    public function transaction_rolls_back_query_exception_and_throwable(): void
    {
        $service = new class extends FileService
        {
            public int $rollbacks = 0;

            public function rollbackFiles(): int
            {
                $this->rollbacks++;

                return $this->rollbacks;
            }
        };

        DB::shouldReceive('beginTransaction')->twice();
        DB::shouldReceive('rollBack')->twice();

        try {
            FileService::transactionWithFileRollback(
                fn (): never => throw new QueryException('testing', 'select 1', [], new Exception('query failed')),
                $service
            );
            $this->fail('Expected query exception.');
        } catch (QueryException) {
            $this->assertSame(1, $service->rollbacks);
        }

        try {
            FileService::transactionWithFileRollback(
                fn (): never => throw new Exception('failed'),
                $service
            );
            $this->fail('Expected exception.');
        } catch (Exception $exception) {
            $this->assertSame('failed', $exception->getMessage());
            $this->assertSame(2, $service->rollbacks);
        }
    }

    #[Test]
    public function open_and_download_return_binary_file_responses(): void
    {
        Storage::disk('local')->put('document/invoices/file.pdf', 'contents');

        $this->assertInstanceOf(BinaryFileResponse::class, FileService::open('document/invoices/file.pdf', 'application/pdf'));
        $this->assertInstanceOf(BinaryFileResponse::class, FileService::download('document/invoices/file.pdf'));
    }

    #[Test]
    public function private_extension_and_image_validation_errors_are_user_friendly(): void
    {
        $pathExtension = new ReflectionMethod(FileService::class, 'getPathExtension');
        $mimeExtension = new ReflectionMethod(FileService::class, 'getFileExtensionFromMime');
        $imageDimensions = new ReflectionMethod(FileService::class, 'ensureImageUploadDimensions');
        $readFileContents = new ReflectionMethod(FileService::class, 'readFileContents');

        try {
            $readFileContents->invoke(null, 'missing.pdf');
            $this->fail('Expected file open exception.');
        } catch (UserFriendlyException $exception) {
            $this->assertSame('The file could not be opened. Please try uploading it again.', $exception->getMessage());
        }

        try {
            $pathExtension->invoke(null, 'file');
            $this->fail('Expected extension detection exception.');
        } catch (UserFriendlyException $exception) {
            $this->assertSame('The file extension could not be detected.', $exception->getMessage());
        }

        try {
            $mimeExtension->invoke(null, random_bytes(32));
            $this->fail('Expected MIME exception.');
        } catch (UserFriendlyException $exception) {
            $this->assertStringContainsString('MIME type', $exception->getMessage());
        }

        try {
            $imageDimensions->invoke(null, 'not-image');
            $this->fail('Expected image exception.');
        } catch (UserFriendlyException $exception) {
            $this->assertSame('The uploaded file is not a valid image.', $exception->getMessage());
        }

        config(['laravel-files.max_upload_image_side_pixels' => 0]);

        $this->expectException(UserFriendlyException::class);
        $this->expectExceptionMessage('1x1px');

        $imageDimensions->invoke(null, base64_decode(self::PNG_CONTENTS));
    }

    #[Test]
    public function private_prepare_image_for_storage_scales_wide_and_tall_images(): void
    {
        $prepare = new ReflectionMethod(FileService::class, 'prepareImageForStorage');
        config(['laravel-files.max_image_side_pixels' => 100]);

        $this->mockImageFacade()
            ->shouldReceive('decodeBinary')
            ->once()
            ->andReturn(new FakeImage(300, 100, 'wide'));

        $this->assertSame('wide-avif-90', $prepare->invoke(null, base64_decode(self::PNG_CONTENTS)));

        $this->mockImageFacade()
            ->shouldReceive('decodeBinary')
            ->once()
            ->andReturn(new FakeImage(100, 300, 'tall'));

        $this->assertSame('tall-avif-90', $prepare->invoke(null, base64_decode(self::PNG_CONTENTS)));
    }

    private function temporaryFile(string $contents, string $extension): string
    {
        $path = tempnam(sys_get_temp_dir(), 'laravel-files-test-');

        if (! empty($extension)) {
            $extensionPath = $path.'.'.$extension;
            rename($path, $extensionPath);
            $path = $extensionPath;
        }

        file_put_contents($path, $contents);

        return $path;
    }

    private function mockImageFacade(): MockInterface
    {
        $mock = Mockery::mock();
        Image::swap($mock);

        return $mock;
    }
}
