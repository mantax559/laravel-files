<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Tests\Unit;

use Mantax559\LaravelFiles\Enums\FileSource;
use Mantax559\LaravelFiles\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class FileSourceTest extends TestCase
{
    #[Test]
    public function enum_exposes_all_sources(): void
    {
        $this->assertSame([
            FileSource::Seeder,
            FileSource::Manual,
        ], FileSource::cases());
    }

    #[Test]
    public function enum_trait_helpers_work(): void
    {
        $this->assertArrayHasKey('seeder', FileSource::getArray());
        $this->assertNotEmpty(FileSource::getArrayForSelect());
        $this->assertSame(FileSource::Manual, FileSource::getEnumByString('MANUAL'));
    }
}
