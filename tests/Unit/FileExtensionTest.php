<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Tests\Unit;

use Mantax559\LaravelFiles\Enums\FileExtension;
use Mantax559\LaravelFiles\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ValueError;

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
    public function category_methods_return_expected_extensions(): void
    {
        $this->assertSame([FileExtension::SevenZip, FileExtension::Gz, FileExtension::Rar, FileExtension::Tar, FileExtension::Zip], FileExtension::archiveExtensions());
        $this->assertSame([FileExtension::Flac, FileExtension::M4a, FileExtension::Mp3, FileExtension::Ogg, FileExtension::Wav], FileExtension::audioExtensions());
        $this->assertContains(FileExtension::Pdf, FileExtension::documentExtensions());
        $this->assertContains(FileExtension::Jpg, FileExtension::imageExtensions());
        $this->assertSame([FileExtension::Avi, FileExtension::Mkv, FileExtension::Mov, FileExtension::Mp4, FileExtension::Webm], FileExtension::videoExtensions());
        $this->assertSame([FileExtension::Json, FileExtension::Xml], FileExtension::fileExtensions());
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
    public function mime_type_resolves_to_extension(): void
    {
        $this->assertSame(FileExtension::Zip, FileExtension::getByMimeType('application/epub+zip'));
        $this->assertSame(FileExtension::Gz, FileExtension::getByMimeType('application/gzip'));
        $this->assertSame(FileExtension::Json, FileExtension::getByMimeType('application/json'));
        $this->assertSame(FileExtension::Doc, FileExtension::getByMimeType('application/msword'));
        $this->assertSame(FileExtension::Pdf, FileExtension::getByMimeType('application/pdf'));
        $this->assertSame(FileExtension::Rtf, FileExtension::getByMimeType('application/rtf'));
        $this->assertSame(FileExtension::Xls, FileExtension::getByMimeType('application/vnd.ms-excel'));
        $this->assertSame(FileExtension::Ppt, FileExtension::getByMimeType('application/vnd.ms-powerpoint'));
        $this->assertSame(FileExtension::Ods, FileExtension::getByMimeType('application/vnd.oasis.opendocument.spreadsheet'));
        $this->assertSame(FileExtension::Odt, FileExtension::getByMimeType('application/vnd.oasis.opendocument.text'));
        $this->assertSame(FileExtension::Pptx, FileExtension::getByMimeType('application/vnd.openxmlformats-officedocument.presentationml.presentation'));
        $this->assertSame(FileExtension::Xlsx, FileExtension::getByMimeType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'));
        $this->assertSame(FileExtension::Docx, FileExtension::getByMimeType('application/vnd.openxmlformats-officedocument.wordprocessingml.document'));
        $this->assertSame(FileExtension::Rar, FileExtension::getByMimeType('application/vnd.rar'));
        $this->assertSame(FileExtension::SevenZip, FileExtension::getByMimeType('application/x-7z-compressed'));
        $this->assertSame(FileExtension::Rar, FileExtension::getByMimeType('application/x-rar-compressed'));
        $this->assertSame(FileExtension::Tar, FileExtension::getByMimeType('application/x-tar'));
        $this->assertSame(FileExtension::Zip, FileExtension::getByMimeType('application/x-zip-compressed'));
        $this->assertSame(FileExtension::Xml, FileExtension::getByMimeType('application/xml'));
        $this->assertSame(FileExtension::Zip, FileExtension::getByMimeType('application/zip'));
        $this->assertSame(FileExtension::Flac, FileExtension::getByMimeType('audio/flac'));
        $this->assertSame(FileExtension::M4a, FileExtension::getByMimeType('audio/m4a'));
        $this->assertSame(FileExtension::Mp3, FileExtension::getByMimeType('audio/mp3'));
        $this->assertSame(FileExtension::M4a, FileExtension::getByMimeType('audio/mp4'));
        $this->assertSame(FileExtension::Mp3, FileExtension::getByMimeType('audio/mpeg'));
        $this->assertSame(FileExtension::Ogg, FileExtension::getByMimeType('audio/ogg'));
        $this->assertSame(FileExtension::Wav, FileExtension::getByMimeType('audio/wav'));
        $this->assertSame(FileExtension::Wav, FileExtension::getByMimeType('audio/x-wav'));
        $this->assertSame(FileExtension::Apng, FileExtension::getByMimeType('image/apng'));
        $this->assertSame(FileExtension::Avif, FileExtension::getByMimeType('image/avif'));
        $this->assertSame(FileExtension::Gif, FileExtension::getByMimeType('image/gif'));
        $this->assertSame(FileExtension::Jpg, FileExtension::getByMimeType('image/jpeg'));
        $this->assertSame(FileExtension::Pjpeg, FileExtension::getByMimeType('image/pjpeg'));
        $this->assertSame(FileExtension::Png, FileExtension::getByMimeType('image/png'));
        $this->assertSame(FileExtension::Svg, FileExtension::getByMimeType('image/svg+xml'));
        $this->assertSame(FileExtension::Webp, FileExtension::getByMimeType('image/webp'));
        $this->assertSame(FileExtension::Csv, FileExtension::getByMimeType('text/csv'));
        $this->assertSame(FileExtension::Txt, FileExtension::getByMimeType('text/plain'));
        $this->assertSame(FileExtension::Xml, FileExtension::getByMimeType('text/xml'));
        $this->assertSame(FileExtension::Avi, FileExtension::getByMimeType('video/avi'));
        $this->assertSame(FileExtension::Mp4, FileExtension::getByMimeType('video/mp4'));
        $this->assertSame(FileExtension::Mov, FileExtension::getByMimeType('video/quicktime'));
        $this->assertSame(FileExtension::Webm, FileExtension::getByMimeType('video/webm'));
        $this->assertSame(FileExtension::Mkv, FileExtension::getByMimeType('video/x-matroska'));
        $this->assertSame(FileExtension::Avi, FileExtension::getByMimeType('video/x-msvideo'));
    }

    #[Test]
    public function unknown_mime_type_throws_value_error(): void
    {
        $this->expectException(ValueError::class);

        FileExtension::getByMimeType('application/unknown');
    }

    #[Test]
    public function enum_trait_helpers_work(): void
    {
        $this->assertArrayHasKey('jpg', FileExtension::getArray());
        $this->assertNotEmpty(FileExtension::getArrayForSelect());
        $this->assertSame(FileExtension::Jpg, FileExtension::getEnumByString('JPG'));
    }
}
