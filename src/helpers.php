<?php

declare(strict_types=1);

use App\Services\FileService;

if (! function_exists('cached_image')) {
    function cached_image(
        string $sourcePath,
        int $width,
        int $height,
        string|int|null $folder = null
    ): string {
        return FileService::cacheImage($sourcePath, $width, $height, $folder);
    }
}
