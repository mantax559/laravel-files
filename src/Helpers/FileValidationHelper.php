<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Helpers;

use Mantax559\LaravelHelpers\Helpers\ValidationHelper;

final class FileValidationHelper
{
    public static function getFileRules(
        string|bool|null $required = null,
        ?int $fileSize = null,
        ?int $minFileSize = null,
        ?int $maxFileSize = null,
        ?string $mimes = null
    ): array {
        return ValidationHelper::mergeRules(
            ValidationHelper::getRequiredRules($required),
            'file',
            self::getFileSizeRules($fileSize, $minFileSize, $maxFileSize),
            'mimes:'.($mimes ?? config('laravel-files.accept_file_mimes')),
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
        ?string $mimes = null
    ): array {
        $dimensions = [];

        if (! empty($width)) {
            $dimensions[] = "width=$width";
        } else {
            if (! empty($minWidth)) {
                $dimensions[] = "min_width=$minWidth";
            }

            $dimensions[] = ! empty($maxWidth)
                ? "max_width=$maxWidth"
                : 'max_width='.config('laravel-files.max_image_dimension');
        }

        if (! empty($height)) {
            $dimensions[] = "height=$height";
        } else {
            if (! empty($minHeight)) {
                $dimensions[] = "min_height=$minHeight";
            }

            $dimensions[] = ! empty($maxHeight)
                ? "max_height=$maxHeight"
                : 'max_height='.config('laravel-files.max_image_dimension');
        }

        return ValidationHelper::mergeRules(
            ValidationHelper::getRequiredRules($required),
            'image',
            self::getFileSizeRules($fileSize, $minFileSize, $maxFileSize),
            'dimensions:'.implode(',', $dimensions),
            'mimes:'.($mimes ?? config('laravel-files.accept_image_mimes')),
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
            $fileSizes[] = 'max:'.config('laravel-files.max_file_size');
        }

        return $fileSizes;
    }
}
