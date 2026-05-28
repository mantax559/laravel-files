<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Message;
use Mantax559\LaravelFiles\Helpers\FileHelper;

if (! function_exists('cached_image')) {
    function cached_image(
        string $sourcePath,
        string $size,
        string|int|array|Model|null $folders = null
    ): string {
        return FileHelper::cacheImage($sourcePath, $size, $folders);
    }
}

if (! function_exists('email_image')) {
    function email_image(
        string $sourcePath,
        string $size,
        Message $message,
        string|int|array|Model|null $folders = null
    ): string {
        return FileHelper::emailImage($sourcePath, $size, $message, $folders);
    }
}
