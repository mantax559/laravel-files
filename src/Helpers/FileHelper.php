<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Helpers;

use Illuminate\Mail\Message;
use Mantax559\LaravelFiles\Models\File;
use Mantax559\LaravelFiles\Services\FileManager;
use Mantax559\LaravelObservability\Models\Log;

final class FileHelper
{
    private const array LOCALHOST_HOSTS = [
        'localhost',
        '127.0.0.1',
        '::1',
    ];

    public static function cacheImage(
        string|File $source,
        string $size
    ): string {
        $sourcePath = self::sourcePath($source);
        [$width, $height] = self::imageCacheSize($sourcePath, $size);

        return FileManager::cacheImage(
            $sourcePath,
            $width,
            $height
        );
    }

    public static function emailImage(
        string|File $source,
        string $size,
        Message $message
    ): string {
        $url = self::cacheImage($source, $size);

        if (self::isLocalhostUrl()) {
            return $message->embed(public_path($url));
        }

        return $url;
    }

    private static function imageCacheSize(string $sourcePath, string $size): array
    {
        if (
            empty(config('laravel-files.image_cache_sizes')[$size]['width'])
            && empty(config('laravel-files.image_cache_sizes')[$size]['height'])
        ) {
            Log::error('Invalid image cache size configuration.', [
                'source_path' => $sourcePath,
                'size' => $size,
                'image_cache_sizes' => config('laravel-files.image_cache_sizes'),
            ]);

            return [null, null];
        }

        return [
            config('laravel-files.image_cache_sizes')[$size]['width'] ?? null,
            config('laravel-files.image_cache_sizes')[$size]['height'] ?? null,
        ];
    }

    private static function sourcePath(string|File $source): string
    {
        if ($source instanceof File) {
            return $source->path;
        }

        return $source;
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
