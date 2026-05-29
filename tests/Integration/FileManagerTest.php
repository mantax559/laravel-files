<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Tests\Integration;

use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Mantax559\LaravelFiles\Enums\FileExtension;
use Mantax559\LaravelFiles\Models\File;
use Mantax559\LaravelFiles\Services\FileManager;
use Mantax559\LaravelFiles\Services\FileTransaction;
use Mantax559\LaravelFiles\Tests\Support\FakeImage;
use Mantax559\LaravelFiles\Tests\TestCase;
use Mantax559\LaravelHelpers\Exceptions\UserFriendlyException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class FileManagerTest extends TestCase
{
    use RefreshDatabase;

    private const string PNG_CONTENTS = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        config([
            'laravel-files.accept_extensions' => [
                FileExtension::Avif,
                FileExtension::Jpg,
                FileExtension::Json,
                FileExtension::Mp3,
                FileExtension::Mp4,
                FileExtension::Pdf,
                FileExtension::Png,
                FileExtension::Txt,
                FileExtension::Webp,
                FileExtension::Zip,
            ],
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
    public function create_converts_supported_images_to_avif(): void
    {
        $this->mockImageFacade()
            ->shouldReceive('decodeBinary')
            ->once()
            ->andReturn(new FakeImage(encodedContents: 'stored-image'));

        $file = FileTransaction::run(fn (FileTransaction $transaction): File => (new FileManager)->create(
            $this->temporaryFile(base64_decode(self::PNG_CONTENTS), 'png'),
            'Products',
            $transaction
        ));

        $this->assertStringStartsWith('image/products/', $file->path);
        $this->assertStringEndsWith('.avif', $file->path);
        $this->assertTrue(Storage::disk('local')->exists($file->path));
        $this->assertSame('stored-image-avif-90', Storage::disk('local')->get($file->path));
        $this->assertSame(FileExtension::STORED_IMAGE_EXTENSION, $file->extension);
    }

    #[Test]
    public function create_keeps_non_convertible_files_in_their_folder(): void
    {
        $file = FileTransaction::run(fn (FileTransaction $transaction): File => (new FileManager)->create(
            $this->temporaryFile('%PDF-1.4', 'pdf'),
            'Invoices',
            $transaction
        ));

        $this->assertStringStartsWith('document/invoices/', $file->path);
        $this->assertStringEndsWith('.pdf', $file->path);
        $this->assertSame('%PDF-1.4', Storage::disk('local')->get($file->path));
        $this->assertDatabaseHas(config('laravel-files.table'), [
            'id' => $file->getKey(),
            'path' => $file->path,
        ]);
    }

    #[Test]
    public function create_rejects_unreadable_paths_and_unsupported_extensions(): void
    {
        $this->expectException(UserFriendlyException::class);
        $this->expectExceptionMessage('The file could not be read');

        (new FileManager)->create('missing-file.pdf', 'Files', new FileTransaction);
    }

    #[Test]
    public function create_rejects_extensions_that_are_not_configured_as_accepted(): void
    {
        config(['laravel-files.accept_extensions' => [FileExtension::Txt]]);

        $this->expectException(UserFriendlyException::class);
        $this->expectExceptionMessage('pdf file format is not allowed');

        (new FileManager)->create($this->temporaryFile('%PDF-1.4', 'pdf'), 'Invoices', new FileTransaction);
    }

    #[Test]
    public function create_accepts_configured_extensions(): void
    {
        config(['laravel-files.accept_extensions' => [FileExtension::Pdf]]);

        $file = FileTransaction::run(fn (FileTransaction $transaction): File => (new FileManager)->create(
            $this->temporaryFile('%PDF-1.4', 'pdf'),
            'Invoices',
            $transaction
        ));

        $this->assertStringEndsWith('.pdf', $file->path);
    }

    #[Test]
    public function create_returns_multiple_files_for_successful_batches(): void
    {
        $files = FileTransaction::run(fn (FileTransaction $transaction): array => (new FileManager)->create([
            $this->temporaryFile('%PDF-1.4', 'pdf'),
            $this->temporaryFile('text', 'txt'),
        ], 'Invoices', $transaction));

        $this->assertCount(2, $files);
        $this->assertStringEndsWith('.pdf', $files[0]->path);
        $this->assertStringEndsWith('.txt', $files[1]->path);
    }

    #[Test]
    public function create_reports_storage_and_upload_size_limits(): void
    {
        config(['laravel-files.max_upload_file_size_bytes' => 4]);

        try {
            (new FileManager)->create($this->temporaryFile('12345', 'pdf'), 'Invoices', new FileTransaction);
            $this->fail('Expected upload size exception.');
        } catch (UserFriendlyException $exception) {
            $this->assertStringContainsString('The file is too large', $exception->getMessage());
        }

        config([
            'laravel-files.max_upload_file_size_bytes' => 1024,
            'laravel-files.max_file_size_bytes' => 4,
        ]);

        $this->expectException(UserFriendlyException::class);
        $this->expectExceptionMessage('The file is too large');

        (new FileManager)->create($this->temporaryFile('12345', 'pdf'), 'Invoices', new FileTransaction);
    }

    #[Test]
    public function storage_write_failures_are_logged_and_reported(): void
    {
        config(['laravel-files.disk' => 'missing']);

        $this->expectException(UserFriendlyException::class);
        $this->expectExceptionMessage('The file could not be stored');

        (new FileManager)->create($this->temporaryFile('%PDF-1.4', 'pdf'), 'Invoices', new FileTransaction);
    }

    #[Test]
    public function cache_image_creates_cache_file_and_reuses_existing_cache(): void
    {
        Storage::disk('local')->put('image/products/source.jpg', 'image');
        $this->mockImageFacade()
            ->shouldReceive('decodePath')
            ->once()
            ->andReturn(new FakeImage(encodedContents: 'cached'));

        $firstUrl = FileManager::cacheImage('image/products/source.jpg', 10, 20, 'Products');
        $secondUrl = FileManager::cacheImage('image/products/source.jpg', 10, 20, 'Products');

        $this->assertTrue(Storage::disk('public')->exists('cache/image/products/source-10x20.avif'));
        $this->assertSame($firstUrl, $secondUrl);
    }

    #[Test]
    public function cache_image_reuses_existing_cache_when_only_one_dimension_is_given(): void
    {
        Storage::disk('local')->put('image/products/source.jpg', 'image');
        $this->mockImageFacade()
            ->shouldReceive('decodePath')
            ->once()
            ->andReturn(new FakeImage(encodedContents: 'cached'));

        $firstUrl = FileManager::cacheImage('image/products/source.jpg', null, 20, 'Products');
        $secondUrl = FileManager::cacheImage('image/products/source.jpg', null, 20, 'Products');

        $this->assertTrue(Storage::disk('public')->exists('cache/image/products/source-autox20.avif'));
        $this->assertSame($firstUrl, $secondUrl);
    }

    #[Test]
    public function cache_image_uses_original_dimensions_when_none_are_given(): void
    {
        Storage::disk('local')->put('image/products/source.jpg', 'image');
        $this->mockImageFacade()
            ->shouldReceive('decodePath')
            ->once()
            ->andReturn(new FakeImage(640, 480, 'cached'));

        FileManager::cacheImage('image/products/source.jpg', null, null, 'Products');

        $this->assertTrue(Storage::disk('public')->exists('cache/image/products/source-autoxauto.avif'));
    }

    #[Test]
    public function cache_image_scales_width_when_only_width_is_given(): void
    {
        Storage::disk('local')->put('image/products/source.jpg', 'image');
        $this->mockImageFacade()
            ->shouldReceive('decodePath')
            ->once()
            ->andReturn(new FakeImage(encodedContents: 'cached'));

        FileManager::cacheImage('image/products/source.jpg', 20, null, 'Products');

        $this->assertTrue(Storage::disk('public')->exists('cache/image/products/source-20xauto.avif'));
    }

    #[Test]
    public function cache_image_returns_default_image_when_generation_throws(): void
    {
        Storage::disk('local')->put('image/products/source.jpg', 'image');
        $this->mockImageFacade()
            ->shouldReceive('decodePath')
            ->once()
            ->andThrow(new RuntimeException('decode failed'));

        $this->assertSame(
            asset(config('laravel-files.default_image_cache_url')),
            FileManager::cacheImage('image/products/source.jpg', 10, 20, 'Products')
        );
    }

    #[Test]
    public function cache_image_fails_when_source_does_not_exist(): void
    {
        $this->assertSame(
            asset(config('laravel-files.default_image_cache_url')),
            FileManager::cacheImage('missing.jpg', 10, 20)
        );
    }

    #[Test]
    public function transaction_removes_uploaded_files_when_callback_throws(): void
    {
        $uploadedPath = null;

        try {
            FileTransaction::run(function (FileTransaction $transaction) use (&$uploadedPath): void {
                $file = (new FileManager)->create($this->temporaryFile('%PDF-1.4', 'pdf'), 'Invoices', $transaction);
                $uploadedPath = $file->path;

                throw new Exception('failed');
            });
            $this->fail('Expected exception.');
        } catch (Exception $exception) {
            $this->assertSame('failed', $exception->getMessage());
        }

        $this->assertFalse(Storage::disk('local')->exists($uploadedPath));
        $this->assertDatabaseCount(config('laravel-files.table'), 0);
        $this->assertRollbackTempEmpty();
    }

    #[Test]
    public function transaction_removes_partially_uploaded_batch_when_later_file_fails(): void
    {
        try {
            FileTransaction::run(fn (FileTransaction $transaction): array => (new FileManager)->create([
                $this->temporaryFile('%PDF-1.4', 'pdf'),
                'missing-file.pdf',
            ], 'Invoices', $transaction));
            $this->fail('Expected exception.');
        } catch (UserFriendlyException $exception) {
            $this->assertStringContainsString('could not be read', $exception->getMessage());
        }

        $this->assertSame([], Storage::disk('local')->allFiles('document/invoices'));
        $this->assertDatabaseCount(config('laravel-files.table'), 0);
        $this->assertRollbackTempEmpty();
    }

    #[Test]
    public function destroy_deletes_source_file_cache_directory_and_model(): void
    {
        $file = $this->createStoredFile('document/invoices/file.pdf', 'contents');
        Storage::disk('public')->put('cache/image/'.$file->getKey().'/thumb.jpg', 'cache');

        FileTransaction::run(function (FileTransaction $transaction) use ($file): void {
            (new FileManager)->destroy($file, $transaction);
        });

        $this->assertFalse(Storage::disk('local')->exists('document/invoices/file.pdf'));
        $this->assertFalse(Storage::disk('public')->exists('cache/image/'.$file->getKey().'/thumb.jpg'));
        $this->assertDatabaseMissing(config('laravel-files.table'), [
            'id' => $file->getKey(),
        ]);
        $this->assertRollbackTempEmpty();
    }

    #[Test]
    public function destroy_restores_source_file_when_callback_throws(): void
    {
        $file = $this->createStoredFile('document/invoices/file.pdf', 'contents');

        try {
            FileTransaction::run(function (FileTransaction $transaction) use ($file): void {
                (new FileManager)->destroy($file, $transaction);

                throw new Exception('failed');
            });
            $this->fail('Expected exception.');
        } catch (Exception $exception) {
            $this->assertSame('failed', $exception->getMessage());
        }

        $this->assertTrue(Storage::disk('local')->exists('document/invoices/file.pdf'));
        $this->assertSame('contents', Storage::disk('local')->get('document/invoices/file.pdf'));
        $this->assertDatabaseHas(config('laravel-files.table'), [
            'id' => $file->getKey(),
        ]);
        $this->assertRollbackTempEmpty();
    }

    #[Test]
    public function destroy_restores_source_file_when_cache_delete_fails(): void
    {
        $file = $this->createStoredFile('document/invoices/file.pdf', 'contents');
        config(['laravel-files.image_cache_disk' => 'missing']);

        try {
            FileTransaction::run(function (FileTransaction $transaction) use ($file): void {
                (new FileManager)->destroy($file, $transaction);
            });
            $this->fail('Expected exception.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('cache directory', $exception->getMessage());
        }

        $this->assertTrue(Storage::disk('local')->exists('document/invoices/file.pdf'));
        $this->assertSame('contents', Storage::disk('local')->get('document/invoices/file.pdf'));
        $this->assertDatabaseHas(config('laravel-files.table'), [
            'id' => $file->getKey(),
        ]);
        $this->assertRollbackTempEmpty();
    }

    #[Test]
    public function destroy_fails_when_source_file_is_missing(): void
    {
        $file = File::query()->create([
            'path' => 'document/invoices/missing.pdf',
            'extension' => FileExtension::Pdf,
            'size' => 8,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('missing file');

        FileTransaction::run(function (FileTransaction $transaction) use ($file): void {
            (new FileManager)->destroy($file, $transaction);
        });
    }

    #[Test]
    public function destroy_throws_when_file_model_cannot_be_deleted(): void
    {
        $file = $this->createStoredFile('document/invoices/file.pdf', 'contents');
        File::deleting(static fn (): bool => false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('file model');

        FileTransaction::run(function (FileTransaction $transaction) use ($file): void {
            (new FileManager)->destroy($file, $transaction);
        });
    }

    #[Test]
    public function open_and_download_return_binary_file_responses(): void
    {
        Storage::disk('local')->put('document/invoices/file.pdf', 'contents');

        $this->assertInstanceOf(BinaryFileResponse::class, FileManager::open('document/invoices/file.pdf', 'application/pdf'));
        $this->assertInstanceOf(BinaryFileResponse::class, FileManager::download('document/invoices/file.pdf'));
    }

    #[Test]
    public function private_extension_and_image_validation_errors_are_user_friendly(): void
    {
        $fileExtension = new ReflectionMethod(FileManager::class, 'getFileExtension');
        $imageDimensions = new ReflectionMethod(FileManager::class, 'ensureUploadImageDimensions');
        $readFileContents = new ReflectionMethod(FileManager::class, 'readFileContents');

        try {
            $readFileContents->invoke(null, 'missing.pdf');
            $this->fail('Expected file open exception.');
        } catch (UserFriendlyException $exception) {
            $this->assertSame('The file could not be opened. Please try uploading it again.', $exception->getMessage());
        }

        try {
            $fileExtension->invoke(null, $this->temporaryFile('test', 'exe'));
            $this->fail('Expected unsupported extension exception.');
        } catch (UserFriendlyException $exception) {
            $this->assertStringContainsString('not supported', $exception->getMessage());
        }

        try {
            $fileExtension->invoke(null, 'file');
            $this->fail('Expected extension detection exception.');
        } catch (UserFriendlyException $exception) {
            $this->assertSame('The file extension could not be detected.', $exception->getMessage());
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

        $imageDimensions->invoke(
            null,
            base64_decode(self::PNG_CONTENTS)
        );
    }

    #[Test]
    public function private_accepted_extensions_text_fails_when_uploads_are_not_configured(): void
    {
        config(['laravel-files.accept_extensions' => []]);

        $this->expectException(UserFriendlyException::class);
        $this->expectExceptionMessage('File uploads are not configured');

        (new FileManager)->create($this->temporaryFile('%PDF-1.4', 'pdf'), 'Invoices', new FileTransaction);
    }

    #[Test]
    public function private_prepare_image_for_storage_scales_wide_and_tall_images(): void
    {
        $prepare = new ReflectionMethod(FileManager::class, 'prepareImageForStorage');
        config(['laravel-files.max_image_side_pixels' => 100]);

        $this->mockImageFacade()
            ->shouldReceive('decodeBinary')
            ->once()
            ->andReturn(new FakeImage(300, 100, 'wide'));

        $this->assertSame('wide-avif-90', $prepare->invoke(null, base64_decode(self::PNG_CONTENTS), FileExtension::STORED_IMAGE_EXTENSION));

        $this->mockImageFacade()
            ->shouldReceive('decodeBinary')
            ->once()
            ->andReturn(new FakeImage(100, 300, 'tall'));

        $this->assertSame('tall-avif-90', $prepare->invoke(null, base64_decode(self::PNG_CONTENTS), FileExtension::STORED_IMAGE_EXTENSION));
    }

    private function createStoredFile(string $path, string $contents): File
    {
        Storage::disk('local')->put($path, $contents);

        return File::query()->create([
            'path' => $path,
            'extension' => FileExtension::Pdf,
            'size' => strlen($contents),
        ]);
    }

    private function assertRollbackTempEmpty(): void
    {
        $this->assertSame([], Storage::disk('local')->allFiles('.rollback-tmp'));
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
