<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Message;
use Mantax559\LaravelFiles\Services\FileService;
use Mantax559\LaravelObservability\Models\Log;

final class FileHelper
{
    private const array LOCALHOST_HOSTS = [
        'localhost',
        '127.0.0.1',
        '::1',
    ];

    public static function cacheImage(
        string $sourcePath,
        string $size,
        string|int|array|Model|null $folderSource = null
    ): string
    {
        [$width, $height] = self::imageCacheSize($sourcePath, $size);

        return FileService::cacheImage(
            $sourcePath,
            $width,
            $height,
            self::normalizeFolders($folderSource)
        );
    }

    public static function emailImage(
        string $sourcePath,
        string $size,
        Message $message,
        string|int|array|Model|null $folderSource = null
    ): string
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

    private static function imageCacheSize(string $sourcePath, string $size): array
    {
        if (! array_key_exists($size, config('laravel-files.image_cache_sizes'))) {
            self::logInvalidImageCacheSize($sourcePath, $size);

            return [null, null];
        }

        if (
            ! empty(config('laravel-files.image_cache_sizes')[$size]['width'])
            || ! empty(config('laravel-files.image_cache_sizes')[$size]['height'])
        ) {
            return [
                empty(config('laravel-files.image_cache_sizes')[$size]['width'])
                    ? null
                    : config('laravel-files.image_cache_sizes')[$size]['width'],
                empty(config('laravel-files.image_cache_sizes')[$size]['height'])
                    ? null
                    : config('laravel-files.image_cache_sizes')[$size]['height'],
            ];
        }

        self::logInvalidImageCacheSize($sourcePath, $size);

        return [null, null];
    }

    private static function logInvalidImageCacheSize(string $sourcePath, string $size): void
    {
        Log::error('Invalid image cache size configuration.', [
            'source_path' => $sourcePath,
            'size' => $size,
            'image_cache_sizes' => config('laravel-files.image_cache_sizes'),
        ]);
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
