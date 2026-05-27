<?php

declare(strict_types=1);

use Mantax559\LaravelFiles\Enums\FileExtension;

return [

    /*
    |--------------------------------------------------------------------------
    | Files Table
    |--------------------------------------------------------------------------
    |
    | This table stores file metadata such as path, extension, source, and size.
    | The package model and migration both read this value, so change it before
    | migrating when your application needs a custom table name.
    |
    */

    'table' => 'files',

    /*
    |--------------------------------------------------------------------------
    | Storage Disk
    |--------------------------------------------------------------------------
    |
    | Uploaded files are stored on this filesystem disk. Returned file paths are
    | relative to this disk, so the same value should be used when opening,
    | downloading, deleting, or restoring stored files.
    |
    */

    'disk' => 'local',

    /*
    |--------------------------------------------------------------------------
    | Image Cache Disk
    |--------------------------------------------------------------------------
    |
    | Cached image thumbnails are stored on this filesystem disk. This disk
    | should be publicly accessible when cached image URLs are returned directly
    | to browsers.
    |
    */

    'image_cache_disk' => 'public',

    /*
    |--------------------------------------------------------------------------
    | Image Cache Quality
    |--------------------------------------------------------------------------
    |
    | This quality value is passed to Intervention Image when encoding stored
    | images and cached image variants.
    |
    */

    'image_cache_quality' => 90,

    /*
    |--------------------------------------------------------------------------
    | Stored File Size Limit
    |--------------------------------------------------------------------------
    |
    | This is the maximum allowed size after package processing. For images this
    | limit is checked after the image has been converted and resized.
    |
    */

    'max_file_size_bytes' => 24 * 1024 * 1024,

    /*
    |--------------------------------------------------------------------------
    | Uploaded File Size Limit
    |--------------------------------------------------------------------------
    |
    | This is the maximum allowed size while reading the original uploaded file.
    | It protects the application before any image processing or conversion runs.
    |
    */

    'max_upload_file_size_bytes' => 48 * 1024 * 1024,

    /*
    |--------------------------------------------------------------------------
    | Stored Image Side Limit
    |--------------------------------------------------------------------------
    |
    | Images larger than this value are scaled down before storage. The longest
    | side is limited while keeping the original aspect ratio.
    |
    */

    'max_image_side_pixels' => 2048,

    /*
    |--------------------------------------------------------------------------
    | Uploaded Image Side Limit
    |--------------------------------------------------------------------------
    |
    | Original uploaded images must not exceed this side length. This validation
    | happens before conversion so extremely large images are rejected early.
    |
    */

    'max_upload_image_side_pixels' => 8192,

    /*
    |--------------------------------------------------------------------------
    | Accepted Archive Extensions
    |--------------------------------------------------------------------------
    |
    | Archive uploads are accepted only when their detected extension exists in
    | this list.
    |
    */

    'accept_archive_extensions' => [
        FileExtension::SevenZip,
        FileExtension::Gz,
        FileExtension::Rar,
        FileExtension::Tar,
        FileExtension::Zip,
    ],

    /*
    |--------------------------------------------------------------------------
    | Accepted Audio Extensions
    |--------------------------------------------------------------------------
    |
    | Audio uploads are accepted only when their detected extension exists in
    | this list.
    |
    */

    'accept_audio_extensions' => [
        FileExtension::M4a,
        FileExtension::Mp3,
        FileExtension::Wav,
    ],

    /*
    |--------------------------------------------------------------------------
    | Accepted Document Extensions
    |--------------------------------------------------------------------------
    |
    | Document uploads are accepted only when their detected extension exists in
    | this list.
    |
    */

    'accept_document_extensions' => [
        FileExtension::Csv,
        FileExtension::Doc,
        FileExtension::Docx,
        FileExtension::Pdf,
        FileExtension::Ppt,
        FileExtension::Pptx,
        FileExtension::Rtf,
        FileExtension::Txt,
        FileExtension::Xls,
        FileExtension::Xlsx,
    ],

    /*
    |--------------------------------------------------------------------------
    | Accepted Image Extensions
    |--------------------------------------------------------------------------
    |
    | Image uploads are accepted only when their detected extension exists in
    | this list. Convertible image uploads are stored as AVIF by default.
    |
    */

    'accept_image_extensions' => [
        FileExtension::Avif,
        FileExtension::Gif,
        FileExtension::Jpeg,
        FileExtension::Jpg,
        FileExtension::Png,
        FileExtension::Svg,
        FileExtension::Webp,
    ],

    /*
    |--------------------------------------------------------------------------
    | Accepted Video Extensions
    |--------------------------------------------------------------------------
    |
    | Video uploads are accepted only when their detected extension exists in
    | this list.
    |
    */

    'accept_video_extensions' => [
        FileExtension::Avi,
        FileExtension::Mkv,
        FileExtension::Mov,
        FileExtension::Mp4,
        FileExtension::Webm,
    ],

    /*
    |--------------------------------------------------------------------------
    | Accepted Generic File Extensions
    |--------------------------------------------------------------------------
    |
    | Extensions that do not belong to archive, audio, document, image, or video
    | categories are accepted only when listed here.
    |
    */

    'accept_file_extensions' => [
        FileExtension::Json,
        FileExtension::Xml,
    ],
];
