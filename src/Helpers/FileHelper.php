<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Message;
use Mantax559\LaravelFiles\Services\FileService;

final class FileHelper
{
    private const array LOCALHOST_HOSTS = [
        'localhost',
        '127.0.0.1',
        '::1',
    ];

    public static function cacheImage(string $sourcePath, string $size, string|int|array|Model|null $folderSource = null): string
    {
        return FileService::cacheImage(
            $sourcePath,
            config('laravel-files.image_cache_sizes')[$size]['width'],
            config('laravel-files.image_cache_sizes')[$size]['height'],
            self::normalizeFolders($folderSource)
        );
    }

    public static function emailImage(string $sourcePath, string $size, Message $message, string|int|array|Model|null $folderSource = null): string
    {
        $url = self::cacheImage($sourcePath, $size, $folderSource);

        if (self::isLocalhostUrl()) {
            return $message->embed(public_path($url));
        }

        return $url;
    }

    private static function normalizeFolders(string|int|array|Model|null $folderSource): string|int|array|null
    {
        if ($folderSource instanceof Model) {
            return [$folderSource->getTable(), $folderSource->getKey()];
        }

        return $folderSource;
    }

    private static function isLocalhostUrl(): bool
    {
        $host = parse_url(config('app.url'), PHP_URL_HOST);

        if (! is_string($host)) {
            return false;
        }

        foreach (self::LOCALHOST_HOSTS as $localhostHost) {
            if (cmprstr($host, $localhostHost)) {
                return true;
            }
        }

        return false;
    }
}
