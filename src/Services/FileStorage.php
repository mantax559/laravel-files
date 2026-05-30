<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Services;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Mantax559\LaravelObservability\Models\Log;
use Throwable;

final class FileStorage
{
    public static function disk(string $disk): FilesystemAdapter
    {
        return Storage::disk($disk);
    }

    public static function path(string ...$parts): string
    {
        return implode('/', $parts);
    }

    public static function save(string $disk, string $filePath, string $fileContents): ?string
    {
        try {
            $saved = self::disk($disk)->put($filePath, $fileContents);
        } catch (Throwable $exception) {
            return Log::error('File save failed.', [
                'disk' => $disk,
                'path' => $filePath,
                'exception' => $exception::class,
                'exception_message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }

        if (! $saved) {
            return Log::error('File save failed.', [
                'disk' => $disk,
                'path' => $filePath,
            ]);
        }

        return null;
    }

    public static function deleteFile(string $disk, string $filePath): bool
    {
        try {
            $deleted = self::disk($disk)->delete($filePath);
        } catch (Throwable $exception) {
            Log::error('File delete failed.', [
                'disk' => $disk,
                'path' => $filePath,
                'exception' => $exception::class,
                'exception_message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return false;
        }

        if ($deleted || ! self::disk($disk)->exists($filePath)) {
            return true;
        }

        Log::error('File delete failed.', [
            'disk' => $disk,
            'path' => $filePath,
        ]);

        return false;
    }

    public static function deleteDirectory(string $disk, string $folderPath): bool
    {
        try {
            $deleted = self::disk($disk)->deleteDirectory($folderPath);
        } catch (Throwable $exception) {
            Log::error('File directory delete failed.', [
                'disk' => $disk,
                'path' => $folderPath,
                'exception' => $exception::class,
                'exception_message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return false;
        }

        if ($deleted || ! self::disk($disk)->directoryExists($folderPath)) {
            return true;
        }

        Log::error('File directory delete failed.', [
            'disk' => $disk,
            'path' => $folderPath,
        ]);

        return false;
    }
}
