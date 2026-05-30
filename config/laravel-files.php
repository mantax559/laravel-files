<?php

declare(strict_types=1);

use Mantax559\LaravelFiles\Enums\FileExtension;

return [

    /*
    |--------------------------------------------------------------------------
    | Files Table
    |--------------------------------------------------------------------------
    |
    | This table stores file metadata such as path, extension, and size.
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
    | Default Image Cache URL
    |--------------------------------------------------------------------------
    |
    | This public URL is returned when an image cache variant cannot be generated.
    | The published default asset can be replaced by the application.
    |
    */

    'default_image_cache_url' => 'vendor/laravel-files/image/default-cache-image.svg',

    /*
    |--------------------------------------------------------------------------
    | Image Cache Sizes
    |--------------------------------------------------------------------------
    |
    | Named image cache sizes allow applications to keep dimensions in config
    | and call helpers by size name instead of passing width and height inline.
    |
    */

    'image_cache_sizes' => [
        // 'logo' => ['height' => 50],
        // 'banner' => ['width' => 1200, 'height' => 800],
    ],

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
    | Accepted Extensions
    |--------------------------------------------------------------------------
    |
    | Uploaded files are accepted only when their detected extension exists in
    | this list. Each extension resolves its own storage folder through the
    | FileExtension enum, so categories do not need separate configuration.
    |
    */

    'accept_extensions' => [
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
    ],
];
