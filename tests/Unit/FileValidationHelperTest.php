<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Tests\Unit;

use Mantax559\LaravelFiles\Enums\FileExtension;
use Mantax559\LaravelFiles\Helpers\FileValidationHelper;
use Mantax559\LaravelFiles\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class FileValidationHelperTest extends TestCase
{
    #[Test]
    public function file_rules_use_all_configured_extensions_by_default(): void
    {
        config([
            'laravel-files.accept_archive_extensions' => [FileExtension::Zip],
            'laravel-files.accept_audio_extensions' => [FileExtension::Mp3],
            'laravel-files.accept_document_extensions' => [FileExtension::Pdf],
            'laravel-files.accept_image_extensions' => [FileExtension::Jpg],
            'laravel-files.accept_video_extensions' => [FileExtension::Mp4],
            'laravel-files.accept_file_extensions' => [FileExtension::Json],
        ]);

        $this->assertSame([
            'required',
            'file',
            'max:49152',
            'mimes:zip,mp3,pdf,jpg,mp4,json',
        ], FileValidationHelper::getFileRules());
    }

    #[Test]
    public function category_rules_use_their_configured_extensions(): void
    {
        config([
            'laravel-files.accept_archive_extensions' => [FileExtension::Zip],
            'laravel-files.accept_audio_extensions' => [FileExtension::Mp3],
            'laravel-files.accept_document_extensions' => [FileExtension::Pdf],
            'laravel-files.accept_video_extensions' => [FileExtension::Mp4],
        ]);

        $this->assertSame(['required', 'file', 'max:49152', 'mimes:zip'], FileValidationHelper::getArchiveRules());
        $this->assertSame(['required', 'file', 'max:49152', 'mimes:mp3'], FileValidationHelper::getAudioRules());
        $this->assertSame(['required', 'file', 'max:49152', 'mimes:pdf'], FileValidationHelper::getDocumentRules());
        $this->assertSame(['required', 'file', 'max:49152', 'mimes:mp4'], FileValidationHelper::getVideoRules());
    }

    #[Test]
    public function image_rules_include_dimensions_and_configured_extensions(): void
    {
        config(['laravel-files.accept_image_extensions' => [FileExtension::Jpg]]);

        $this->assertSame([
            'required',
            'image',
            'max:49152',
            'dimensions:width=100,min_height=50,max_height=200',
            'mimes:jpg',
        ], FileValidationHelper::getImageRules(width: 100, minHeight: 50, maxHeight: 200));

        $this->assertSame([
            'required',
            'image',
            'max:49152',
            'dimensions:max_width=8192,max_height=8192',
            'mimes:jpg',
        ], FileValidationHelper::getImageRules());
    }

    #[Test]
    public function file_size_rules_support_exact_min_max_and_custom_mimes(): void
    {
        $this->assertSame([
            'required',
            'file',
            'size:12',
            'mimes:pdf,XLSX',
        ], FileValidationHelper::getFileRules(required: true, fileSize: 12, mimes: [FileExtension::Pdf, 'XLSX']));

        $this->assertSame([
            'nullable',
            'file',
            'min:2',
            'max:4',
            'mimes:pdf',
        ], FileValidationHelper::getDocumentRules(required: false, minFileSize: 2, maxFileSize: 4, mimes: [FileExtension::Pdf]));
    }
}
