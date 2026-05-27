<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Helpers;

use Mantax559\LaravelFiles\Enums\FileExtension;
use Mantax559\LaravelHelpers\Helpers\ValidationHelper;

final class FileValidationHelper
{
    private const BYTES_IN_KILOBYTE = 1024;

    public static function getArchiveRules(
        string|bool|null $required = null,
        ?int $fileSize = null,
        ?int $minFileSize = null,
        ?int $maxFileSize = null,
        ?array $mimes = null
    ): array {
        return ValidationHelper::mergeRules(
            ValidationHelper::getRequiredRules($required),
            'file',
            self::getFileSizeRules($fileSize, $minFileSize, $maxFileSize),
            self::getMimesRule($mimes ?? config('laravel-files.accept_archive_extensions')),
        );
    }

    public static function getAudioRules(
        string|bool|null $required = null,
        ?int $fileSize = null,
        ?int $minFileSize = null,
        ?int $maxFileSize = null,
        ?array $mimes = null
    ): array {
        return ValidationHelper::mergeRules(
            ValidationHelper::getRequiredRules($required),
            'file',
            self::getFileSizeRules($fileSize, $minFileSize, $maxFileSize),
            self::getMimesRule($mimes ?? config('laravel-files.accept_audio_extensions')),
        );
    }

    public static function getDocumentRules(
        string|bool|null $required = null,
        ?int $fileSize = null,
        ?int $minFileSize = null,
        ?int $maxFileSize = null,
        ?array $mimes = null
    ): array {
        return ValidationHelper::mergeRules(
            ValidationHelper::getRequiredRules($required),
            'file',
            self::getFileSizeRules($fileSize, $minFileSize, $maxFileSize),
            self::getMimesRule($mimes ?? config('laravel-files.accept_document_extensions')),
        );
    }

    public static function getImageRules(
        string|bool|null $required = null,
        ?int $fileSize = null,
        ?int $minFileSize = null,
        ?int $maxFileSize = null,
        ?int $width = null,
        ?int $height = null,
        ?int $minWidth = null,
        ?int $minHeight = null,
        ?int $maxWidth = null,
        ?int $maxHeight = null,
        ?array $mimes = null
    ): array {
        return ValidationHelper::mergeRules(
            ValidationHelper::getRequiredRules($required),
            'image',
            self::getFileSizeRules($fileSize, $minFileSize, $maxFileSize),
            'dimensions:'.implode(',', [
                ...self::getImageDimensionRules('width', $width, $minWidth, $maxWidth),
                ...self::getImageDimensionRules('height', $height, $minHeight, $maxHeight),
            ]),
            self::getMimesRule($mimes ?? config('laravel-files.accept_image_extensions')),
        );
    }

    public static function getVideoRules(
        string|bool|null $required = null,
        ?int $fileSize = null,
        ?int $minFileSize = null,
        ?int $maxFileSize = null,
        ?array $mimes = null
    ): array {
        return ValidationHelper::mergeRules(
            ValidationHelper::getRequiredRules($required),
            'file',
            self::getFileSizeRules($fileSize, $minFileSize, $maxFileSize),
            self::getMimesRule($mimes ?? config('laravel-files.accept_video_extensions')),
        );
    }

    public static function getFileRules(
        string|bool|null $required = null,
        ?int $fileSize = null,
        ?int $minFileSize = null,
        ?int $maxFileSize = null,
        ?array $mimes = null
    ): array {
        return ValidationHelper::mergeRules(
            ValidationHelper::getRequiredRules($required),
            'file',
            self::getFileSizeRules($fileSize, $minFileSize, $maxFileSize),
            self::getMimesRule($mimes ?? self::getAllAcceptedExtensions()),
        );
    }

    private static function getFileSizeRules(
        ?int $fileSize = null,
        ?int $minFileSize = null,
        ?int $maxFileSize = null
    ): array {
        if (! empty($fileSize)) {
            return ["size:$fileSize"];
        }

        $fileSizes = [];

        if (! empty($minFileSize)) {
            $fileSizes[] = "min:$minFileSize";
        }

        if (! empty($maxFileSize)) {
            $fileSizes[] = "max:$maxFileSize";
        } else {
            $fileSizes[] = 'max:'.ceil(config('laravel-files.max_upload_file_size_bytes') / self::BYTES_IN_KILOBYTE);
        }

        return $fileSizes;
    }

    private static function getImageDimensionRules(
        string $side,
        ?int $exact,
        ?int $min,
        ?int $max
    ): array {
        if (! empty($exact)) {
            return ["$side=$exact"];
        }

        $dimensions = [];

        if (! empty($min)) {
            $dimensions[] = "min_$side=$min";
        }

        $dimensions[] = ! empty($max)
            ? "max_$side=$max"
            : 'max_'.$side.'='.config('laravel-files.max_upload_image_side_pixels');

        return $dimensions;
    }

    private static function getMimesRule(array $mimes): string
    {
        return 'mimes:'.implode(',', self::getMimeValues($mimes));
    }

    private static function getMimeValues(array $mimes): array
    {
        return collect($mimes)
            ->map(fn (FileExtension|string $mime): string => $mime instanceof FileExtension ? $mime->value : strtolower($mime))
            ->all();
    }

    private static function getAllAcceptedExtensions(): array
    {
        return [
            ...config('laravel-files.accept_archive_extensions'),
            ...config('laravel-files.accept_audio_extensions'),
            ...config('laravel-files.accept_document_extensions'),
            ...config('laravel-files.accept_image_extensions'),
            ...config('laravel-files.accept_video_extensions'),
            ...config('laravel-files.accept_file_extensions'),
        ];
    }
}
