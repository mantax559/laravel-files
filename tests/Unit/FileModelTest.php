<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Tests\Unit;

use Mantax559\LaravelFiles\Enums\FileExtension;
use Mantax559\LaravelFiles\Enums\FileSource;
use Mantax559\LaravelFiles\Models\File;
use Mantax559\LaravelFiles\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class FileModelTest extends TestCase
{
    #[Test]
    public function model_uses_configured_table_and_casts(): void
    {
        config(['laravel-files.table' => 'custom_files']);

        $file = new File([
            'extension' => FileExtension::Jpg->value,
            'size' => '15',
            'source' => FileSource::Manual->value,
        ]);

        $this->assertSame('custom_files', $file->getTable());
        $this->assertSame(FileExtension::Jpg, $file->extension);
        $this->assertSame(FileSource::Manual, $file->source);
        $this->assertSame('integer', $file->getCasts()['size']);
        $this->assertTrue($file->usesTimestamps());
        $this->assertContains('path', $file->getFillable());
        $this->assertSame('deleted_at', $file->getDeletedAtColumn());
    }
}
