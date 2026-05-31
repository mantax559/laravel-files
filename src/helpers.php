<?php

declare(strict_types=1);

use Illuminate\Mail\Message;
use Mantax559\LaravelFiles\Helpers\FileHelper;
use Mantax559\LaravelFiles\Models\File;

if (! function_exists('cache_image')) {
    function cache_image(
        string|File $source,
        string $size
    ): string {
        return FileHelper::cacheImage($source, $size);
    }
}

if (! function_exists('email_image')) {
    function email_image(
        string|File $source,
        string $size,
        Message $message
    ): string {
        return FileHelper::emailImage($source, $size, $message);
    }
}
