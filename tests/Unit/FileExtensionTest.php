<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Tests\Unit;

use Mantax559\LaravelFiles\Enums\FileExtension;
use Mantax559\LaravelFiles\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class FileExtensionTest extends TestCase
{
    #[Test]
    public function enum_exposes_cases_sorted_by_extension_value(): void
    {
        $this->assertSame([
            FileExtension::SevenZip,
            FileExtension::Apng,
            FileExtension::Avi,
            FileExtension::Avif,
            FileExtension::Csv,
            FileExtension::Doc,
            FileExtension::Docx,
            FileExtension::Flac,
            FileExtension::Gif,
            FileExtension::Gz,
            FileExtension::Jfif,
            FileExtension::Jpeg,
            FileExtension::Jpg,
            FileExtension::Json,
            FileExtension::M4a,
            FileExtension::Mkv,
            FileExtension::Mov,
            FileExtension::Mp3,
            FileExtension::Mp4,
            FileExtension::Ods,
            FileExtension::Odt,
            FileExtension::Ogg,
            FileExtension::Pdf,
            FileExtension::Pjpeg,
            FileExtension::Pjp,
            FileExtension::Png,
            FileExtension::Ppt,
            FileExtension::Pptx,
            FileExtension::Rar,
            FileExtension::Rtf,
            FileExtension::Svg,
            FileExtension::Tar,
            FileExtension::Txt,
            FileExtension::Wav,
            FileExtension::Webm,
            FileExtension::Webp,
            FileExtension::Xls,
            FileExtension::Xlsx,
            FileExtension::Xml,
            FileExtension::Zip,
        ], FileExtension::cases());
    }

    #[Test]
    public function extension_resolves_storage_folder(): void
    {
        $this->assertSame(FileExtension::FOLDER_ARCHIVE, FileExtension::Zip->folder());
        $this->assertSame(FileExtension::FOLDER_AUDIO, FileExtension::Mp3->folder());
        $this->assertSame(FileExtension::FOLDER_DOCUMENT, FileExtension::Pdf->folder());
        $this->assertSame(FileExtension::FOLDER_IMAGE, FileExtension::Jpg->folder());
        $this->assertSame(FileExtension::FOLDER_VIDEO, FileExtension::Mp4->folder());
        $this->assertSame(FileExtension::FOLDER_FILE, FileExtension::Json->folder());
    }

    #[Test]
    public function extension_knows_when_it_can_be_converted_to_avif(): void
    {
        $this->assertTrue(FileExtension::Avif->isConvertibleToAvif());
        $this->assertTrue(FileExtension::Jpeg->isConvertibleToAvif());
        $this->assertTrue(FileExtension::Jpg->isConvertibleToAvif());
        $this->assertTrue(FileExtension::Png->isConvertibleToAvif());
        $this->assertTrue(FileExtension::Webp->isConvertibleToAvif());
        $this->assertFalse(FileExtension::Pdf->isConvertibleToAvif());
    }

    #[Test]
    public function extensions_can_be_filtered_by_storage_folder(): void
    {
        $extensions = [
            FileExtension::Csv,
            FileExtension::Jpg,
            'png',
            'missing',
        ];

        $this->assertSame([FileExtension::Jpg, 'png'], FileExtension::filterByFolder($extensions, FileExtension::FOLDER_IMAGE));
        $this->assertSame($extensions, FileExtension::filterByFolder($extensions));
    }

    #[Test]
    public function enum_trait_helpers_work(): void
    {
        $this->assertArrayHasKey('jpg', FileExtension::getArray());
        $this->assertNotEmpty(FileExtension::getArrayForSelect());
        $this->assertSame(FileExtension::Jpg, FileExtension::getEnumByString('JPG'));
    }
}
