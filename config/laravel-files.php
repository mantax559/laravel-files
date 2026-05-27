<?php

declare(strict_types=1);

use Mantax559\LaravelFiles\Enums\FileExtension;

return [
    'table' => 'files',
    'disk' => 'local',
    'image_cache_disk' => 'public',
    'image_cache_quality' => 90,
    'max_file_size_bytes' => 24 * 1024 * 1024,
    'max_upload_file_size_bytes' => 48 * 1024 * 1024,
    'max_image_side_pixels' => 2048,
    'max_upload_image_side_pixels' => 8192,
    'accept_image_extensions' => [
        FileExtension::Apng,
        FileExtension::Avif,
        FileExtension::Gif,
        FileExtension::Jfif,
        FileExtension::Jpeg,
        FileExtension::Jpg,
        FileExtension::Pjp,
        FileExtension::Pjpeg,
        FileExtension::Png,
        FileExtension::Svg,
        FileExtension::Webp,
    ],
    'accept_document_extensions' => [
        FileExtension::Csv,
        FileExtension::Doc,
        FileExtension::Docx,
        FileExtension::Ods,
        FileExtension::Odt,
        FileExtension::Pdf,
        FileExtension::Ppt,
        FileExtension::Pptx,
        FileExtension::Rtf,
        FileExtension::Txt,
        FileExtension::Xls,
        FileExtension::Xlsx,
    ],
    'accept_video_extensions' => [
        FileExtension::Avi,
        FileExtension::Mkv,
        FileExtension::Mov,
        FileExtension::Mp4,
        FileExtension::Webm,
    ],
    'accept_audio_extensions' => [
        FileExtension::Flac,
        FileExtension::M4a,
        FileExtension::Mp3,
        FileExtension::Ogg,
        FileExtension::Wav,
    ],
    'accept_archive_extensions' => [
        FileExtension::SevenZip,
        FileExtension::Gz,
        FileExtension::Rar,
        FileExtension::Tar,
        FileExtension::Zip,
    ],
    'accept_file_extensions' => [
        FileExtension::Json,
        FileExtension::Xml,
    ],
];