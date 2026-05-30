<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Tests\Unit;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Mantax559\LaravelFiles\Services\FileStorage;
use Mantax559\LaravelFiles\Tests\TestCase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

final class FileStorageTest extends TestCase
{
    #[Test]
    public function save_returns_log_code_when_disk_reports_failure(): void
    {
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('put')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);

        $this->assertNotEmpty(FileStorage::save('local', 'file.txt', 'contents'));
    }

    #[Test]
    public function delete_file_returns_false_when_disk_throws(): void
    {
        Storage::shouldReceive('disk')->with('local')->andThrow(new RuntimeException('disk failed'));

        $this->assertFalse(FileStorage::deleteFile('local', 'file.txt'));
    }

    #[Test]
    public function delete_file_returns_false_when_file_still_exists(): void
    {
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('delete')->once()->with('file.txt')->andReturn(false);
        $disk->shouldReceive('exists')->once()->with('file.txt')->andReturn(true);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);

        $this->assertFalse(FileStorage::deleteFile('local', 'file.txt'));
    }

    #[Test]
    public function delete_directory_returns_false_when_directory_still_exists(): void
    {
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('deleteDirectory')->once()->with('folder')->andReturn(false);
        $disk->shouldReceive('directoryExists')->once()->with('folder')->andReturn(true);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);

        $this->assertFalse(FileStorage::deleteDirectory('local', 'folder'));
    }
}
