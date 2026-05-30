<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Helpers;

use Mantax559\LaravelFiles\Enums\FileExtension;
use Mantax559\LaravelHelpers\Helpers\ValidationHelper;

final class FileValidationHelper
{
    private const int BYTES_IN_KILOBYTE = 1024;

    private const string RULE_FILE = 'file';

    private const string RULE_IMAGE = 'image';

    private const string RULE_SIZE = 'size';

    private const string RULE_MIN = 'min';

    private const string RULE_MAX = 'max';

    private const string RULE_MIMES = 'mimes';

    private const string RULE_DIMENSIONS = 'dimensions';

    private const string DIMENSION_WIDTH = 'width';

    private const string DIMENSION_HEIGHT = 'height';

    public static function getArchiveRules(
        string|bool|null $required = null,
        ?int $fileSize = null,
        ?int $minFileSize = null,
        ?int $maxFileSize = null,
        ?array $mimes = null
    ): array {
        return ValidationHelper::mergeRules(
            ValidationHelper::getRequiredRules($required),
            self::RULE_FILE,
            self::getFileSizeRules($fileSize, $minFileSize, $maxFileSize),
            self::getMimesRule($mimes ?? FileExtension::acceptedExtensions(FileExtension::FOLDER_ARCHIVE)),
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
            self::RULE_FILE,
            self::getFileSizeRules($fileSize, $minFileSize, $maxFileSize),
            self::getMimesRule($mimes ?? FileExtension::acceptedExtensions(FileExtension::FOLDER_AUDIO)),
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
            self::RULE_FILE,
            self::getFileSizeRules($fileSize, $minFileSize, $maxFileSize),
            self::getMimesRule($mimes ?? FileExtension::acceptedExtensions(FileExtension::FOLDER_DOCUMENT)),
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
            self::RULE_IMAGE,
            self::getFileSizeRules($fileSize, $minFileSize, $maxFileSize),
            self::RULE_DIMENSIONS.':'.implode(',', [
                ...self::getImageDimensionRules(self::DIMENSION_WIDTH, $width, $minWidth, $maxWidth),
                ...self::getImageDimensionRules(self::DIMENSION_HEIGHT, $height, $minHeight, $maxHeight),
            ]),
            self::getMimesRule($mimes ?? FileExtension::acceptedExtensions(FileExtension::FOLDER_IMAGE)),
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
            self::RULE_FILE,
            self::getFileSizeRules($fileSize, $minFileSize, $maxFileSize),
            self::getMimesRule($mimes ?? FileExtension::acceptedExtensions(FileExtension::FOLDER_VIDEO)),
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
            self::RULE_FILE,
            self::getFileSizeRules($fileSize, $minFileSize, $maxFileSize),
            self::getMimesRule($mimes ?? config('laravel-files.accept_extensions')),
        );
    }

    private static function getFileSizeRules(
        ?int $fileSize = null,
        ?int $minFileSize = null,
        ?int $maxFileSize = null
    ): array {
        if (! empty($fileSize)) {
            return [self::RULE_SIZE.':'.$fileSize];
        }

        $fileSizes = [];

        if (! empty($minFileSize)) {
            $fileSizes[] = self::RULE_MIN.':'.$minFileSize;
        }

        if (! empty($maxFileSize)) {
            $fileSizes[] = self::RULE_MAX.':'.$maxFileSize;
        } else {
            $fileSizes[] = self::RULE_MAX.':'.floor(config('laravel-files.max_upload_file_size_bytes') / self::BYTES_IN_KILOBYTE);
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
            return [$side.'='.$exact];
        }

        $dimensions = [];

        if (! empty($min)) {
            $dimensions[] = self::RULE_MIN.'_'.$side.'='.$min;
        }

        $dimensions[] = ! empty($max)
            ? self::RULE_MAX.'_'.$side.'='.$max
            : self::RULE_MAX.'_'.$side.'='.config('laravel-files.max_upload_image_side_pixels');

        return $dimensions;
    }

    private static function getMimesRule(array $mimes): string
    {
        return self::RULE_MIMES.':'.implode(',', self::getMimeValues($mimes));
    }

    private static function getMimeValues(array $mimes): array
    {
        $mimeValues = [];

        foreach ($mimes as $mime) {
            $mimeValues[] = $mime instanceof FileExtension
                ? $mime->value
                : $mime;
        }

        return $mimeValues;
    }
}
