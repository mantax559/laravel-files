<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Message;
use Mantax559\LaravelFiles\Helpers\FileHelper;

if (! function_exists('cache_image')) {
    function cache_image(
        string $sourcePath,
        string $size,
        string|int|array|Model|null $folderSource = null
    ): string {
        return FileHelper::cacheImage($sourcePath, $size, $folderSource);
    }
}

if (! function_exists('email_image')) {
    function email_image(
        string $sourcePath,
        string $size,
        Message $message,
        string|int|array|Model|null $folderSource = null
    ): string {
        return FileHelper::emailImage($sourcePath, $size, $message, $folderSource);
    }
}
