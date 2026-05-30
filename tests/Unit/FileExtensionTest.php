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
    public function extension_knows_when_it_is_an_image(): void
    {
        $this->assertSame(FileExtension::Avif, FileExtension::STORED_IMAGE_EXTENSION);
        $this->assertTrue(FileExtension::Avif->isImage());
        $this->assertTrue(FileExtension::Gif->isImage());
        $this->assertTrue(FileExtension::Jpg->isImage());
        $this->assertTrue(FileExtension::Png->isImage());
        $this->assertTrue(FileExtension::Webp->isImage());
        $this->assertFalse(FileExtension::Pdf->isImage());
        $this->assertSame(FileExtension::STORED_IMAGE_EXTENSION, FileExtension::Jpg->storageImageExtension());
        $this->assertSame(FileExtension::Gif, FileExtension::Gif->storageImageExtension());
    }

    #[Test]
    public function extension_resolves_content_type(): void
    {
        $contentTypes = [];

        foreach (FileExtension::cases() as $fileExtension) {
            $contentTypes[$fileExtension->value] = $fileExtension->contentType();
        }

        $this->assertSame([
            '7z' => 'application/x-7z-compressed',
            'apng' => 'image/apng',
            'avi' => 'video/x-msvideo',
            'avif' => 'image/avif',
            'csv' => 'text/csv',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'flac' => 'audio/flac',
            'gif' => 'image/gif',
            'gz' => 'application/gzip',
            'jfif' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'json' => 'application/json',
            'm4a' => 'audio/mp4',
            'mkv' => 'video/x-matroska',
            'mov' => 'video/quicktime',
            'mp3' => 'audio/mpeg',
            'mp4' => 'video/mp4',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ogg' => 'audio/ogg',
            'pdf' => 'application/pdf',
            'pjpeg' => 'image/jpeg',
            'pjp' => 'image/jpeg',
            'png' => 'image/png',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'rar' => 'application/vnd.rar',
            'rtf' => 'application/rtf',
            'svg' => 'image/svg+xml',
            'tar' => 'application/x-tar',
            'txt' => 'text/plain',
            'wav' => 'audio/wav',
            'webm' => 'video/webm',
            'webp' => 'image/webp',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xml' => 'application/xml',
            'zip' => 'application/zip',
        ], $contentTypes);
    }

    #[Test]
    public function extensions_can_be_filtered_by_storage_folder(): void
    {
        config(['laravel-files.accept_extensions' => [
            FileExtension::Csv,
            FileExtension::Jpg,
            FileExtension::Png,
        ]]);

        $this->assertSame([FileExtension::Jpg, FileExtension::Png], FileExtension::acceptedExtensions(FileExtension::FOLDER_IMAGE));
        $this->assertSame(config('laravel-files.accept_extensions'), FileExtension::acceptedExtensions());
    }

    #[Test]
    public function enum_trait_helpers_work(): void
    {
        $this->assertArrayHasKey('jpg', FileExtension::getArray());
        $this->assertNotEmpty(FileExtension::getArrayForSelect());
        $this->assertSame(FileExtension::Jpg, FileExtension::getEnumByString('JPG'));
    }
}
