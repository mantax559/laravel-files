<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Services;

use Illuminate\Support\Facades\File as FileFacade;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Mantax559\LaravelFiles\Enums\FileExtension;
use Mantax559\LaravelFiles\Enums\FileSource;
use Mantax559\LaravelFiles\Models\File;
use Mantax559\LaravelHelpers\Exceptions\UserFriendlyException;
use Mantax559\LaravelObservability\Models\Log;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;
use ValueError;

class FileManager
{
    private const string FOLDER_CACHE = 'cache';

    private const string FOLDER_SEEDER = 'seeder';

    private const string CACHE_AUTO_DIMENSION = 'auto';

    private const string DEFAULT_CACHE_IMAGE_PATH = 'vendor/laravel-files/image/default-cache-image.svg';

    private const int FILE_READ_CHUNK_BYTES = 1024 * 1024;

    public function __construct(private FileSource $fileSource = FileSource::Manual) {}

    public function create(string|array $files, string $folder, FileTransaction $transaction): File|array
    {
        if (! is_array($files)) {
            return $this->createFile($files, $folder, $transaction);
        }

        $models = [];

        foreach ($files as $file) {
            $models[] = $this->createFile($file, $folder, $transaction);
        }

        return $models;
    }

    public function destroy(File|array $files, FileTransaction $transaction): void
    {
        foreach (is_array($files) ? $files : [$files] as $file) {
            $this->deleteFiles($file, $transaction);

            if (! $file->delete()) {
                throw new RuntimeException(__('The file model could not be deleted.'));
            }
        }
    }

    private function save(string $file, string $folder, FileTransaction $transaction): array
    {
        if (! FileFacade::isFile($file) && ! Str::isUrl($file)) {
            throw new UserFriendlyException(__('The file could not be read.'));
        }

        $fileExtension = self::getFileExtension($file);
        self::ensureAcceptedFileExtension($fileExtension);

        $fileContents = self::readFileContents($file);

        if ($fileExtension->isImage()) {
            self::ensureUploadImageDimensions($fileContents);

            $fileExtension = $fileExtension->storageImageExtension();
            $fileContents = self::prepareImageForStorage($fileContents, $fileExtension);
        }

        self::ensureFileSize($fileContents, config('laravel-files.max_file_size_bytes'));

        $this->deleteSeederFolder($fileExtension, $folder, $transaction);

        $filePath = FileStorage::path(
            $this->getStorageFolderPath($fileExtension, $folder),
            Str::uuid7()->toString().'.'.$fileExtension->value
        );

        $errorCode = FileStorage::save(config('laravel-files.disk'), $filePath, $fileContents);

        if (! empty($errorCode)) {
            throw new UserFriendlyException(__(
                'The file could not be stored. Please contact support with the following code: :error_code',
                ['error_code' => $errorCode]
            ));
        }

        $transaction->addUploaded($filePath);

        return [
            'path' => $filePath,
            'extension' => $fileExtension,
            'source' => $this->fileSource,
            'size' => strlen($fileContents),
        ];
    }

    public static function cacheImage(
        string $sourcePath,
        ?int $width = null,
        ?int $height = null,
        string|int|array|null $folderSource = null
    ): string {
        if (! FileStorage::disk(config('laravel-files.disk'))->exists($sourcePath)) {
            Log::error('Image cache source file is missing.', [
                'disk' => config('laravel-files.disk'),
                'path' => $sourcePath,
            ]);

            return self::defaultCacheImageUrl();
        }

        $sourceInfo = pathinfo($sourcePath);
        self::getFileExtension($sourcePath);
        $coverImage = ! empty($width) && ! empty($height);
        $cachePath = self::getCacheImagePath($sourceInfo['filename'], $width, $height, $folderSource);

        if (FileStorage::disk(config('laravel-files.image_cache_disk'))->exists($cachePath)) {
            return FileStorage::disk(config('laravel-files.image_cache_disk'))->url($cachePath);
        }

        try {
            $image = Image::decodePath(FileStorage::disk(config('laravel-files.disk'))->path($sourcePath));

            if (empty($width) && empty($height)) {
                $width = $image->width();
                $height = $image->height();
            } elseif (empty($width)) {
                $image = $image->scale(height: $height);
                $width = $image->width();
            } elseif (empty($height)) {
                $image = $image->scale(width: $width);
                $height = $image->height();
            }

            if ($coverImage) {
                $image = $image->cover($width, $height);
            }

            $errorCode = FileStorage::save(
                config('laravel-files.image_cache_disk'),
                $cachePath,
                $image->encodeUsingFileExtension(FileExtension::STORED_IMAGE_EXTENSION->value, quality: config('laravel-files.image_cache_quality'))->toString()
            );

            if (! empty($errorCode)) {
                return self::defaultCacheImageUrl();
            }
        } catch (Throwable $exception) {
            Log::error('Image cache generation failed.', [
                'disk' => config('laravel-files.disk'),
                'image_cache_disk' => config('laravel-files.image_cache_disk'),
                'source_path' => $sourcePath,
                'cache_path' => $cachePath,
                'exception' => $exception::class,
                'exception_message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return self::defaultCacheImageUrl();
        }

        return FileStorage::disk(config('laravel-files.image_cache_disk'))->url($cachePath);
    }

    public static function open(string $filePath, string $headerContentType): BinaryFileResponse
    {
        return response()->file(FileStorage::disk(config('laravel-files.disk'))->path($filePath), ['Content-Type' => $headerContentType]);
    }

    public static function download(string $filePath): BinaryFileResponse
    {
        return response()->download(FileStorage::disk(config('laravel-files.disk'))->path($filePath));
    }

    private function createFile(string $file, string $folder, FileTransaction $transaction): File
    {
        return File::create($this->save($file, $folder, $transaction));
    }

    private function deleteFiles(File $file, FileTransaction $transaction): void
    {
        if (! FileStorage::disk(config('laravel-files.disk'))->exists($file->path)) {
            Log::error('File model references missing file.', [
                'disk' => config('laravel-files.disk'),
                'path' => $file->path,
                'file_id' => $file->getKey(),
            ]);

            throw new RuntimeException(__('The file model references a missing file.'));
        }

        $transaction->addDeleted($file->path);

        if (! FileStorage::deleteDirectory(config('laravel-files.image_cache_disk'), self::getCacheImageFolder($file->getKey()))) {
            throw new RuntimeException(__('The file cache directory could not be deleted.'));
        }
    }

    private function deleteSeederFolder(FileExtension $fileExtension, string $folder, FileTransaction $transaction): void
    {
        if (! cmprenum($this->fileSource, FileSource::Seeder)) {
            return;
        }

        $folderPath = $this->getStorageFolderPath($fileExtension, $folder);
        $transaction->addDeletedDirectory($folderPath);
    }

    private function getStorageFolderPath(FileExtension $fileExtension, string $folder): string
    {
        $parts = [];

        if (cmprenum($this->fileSource, FileSource::Seeder)) {
            $parts[] = self::FOLDER_SEEDER;
        }

        $parts[] = $fileExtension->folder();
        $parts[] = slugify($folder);

        return FileStorage::path(...$parts);
    }

    private static function getCacheImageFolder(string|int|array|null $folderSource = null): string
    {
        $parts = [self::FOLDER_CACHE, FileExtension::FOLDER_IMAGE];

        foreach (is_array($folderSource) ? $folderSource : [$folderSource] as $folder) {
            if (filled($folder)) {
                $parts[] = slugify($folder);
            }
        }

        return FileStorage::path(...$parts);
    }

    private static function getCacheImagePath(string $filename, ?int $width, ?int $height, string|int|array|null $folderSource = null): string
    {
        return FileStorage::path(
            self::getCacheImageFolder($folderSource),
            slugify($filename).'-'.($width ?? self::CACHE_AUTO_DIMENSION).'x'.($height ?? self::CACHE_AUTO_DIMENSION).'.'.FileExtension::STORED_IMAGE_EXTENSION->value
        );
    }

    private static function defaultCacheImageUrl(): string
    {
        return asset(self::DEFAULT_CACHE_IMAGE_PATH);
    }

    private static function readFileContents(string $file): string
    {
        $handle = @fopen($file, 'rb');

        if (! $handle) {
            throw new UserFriendlyException(__('The file could not be opened. Please try uploading it again.'));
        }

        $contents = '';

        while (! feof($handle)) {
            $chunk = fread($handle, self::FILE_READ_CHUNK_BYTES);
            $contents .= $chunk;

            self::ensureFileSize($contents, config('laravel-files.max_upload_file_size_bytes'));
        }

        fclose($handle);

        return $contents;
    }

    private static function getFileExtension(string $path): FileExtension
    {
        $urlPath = parse_url($path, PHP_URL_PATH);
        $extensionPath = is_string($urlPath) ? $urlPath : $path;
        $extension = pathinfo($extensionPath, PATHINFO_EXTENSION);

        if (! empty($extension)) {
            return self::parseFileExtension($extension);
        }

        throw new UserFriendlyException(__('The file extension could not be detected.'));
    }

    private static function ensureAcceptedFileExtension(FileExtension $fileExtension): void
    {
        if (self::containsExtension(FileExtension::acceptedExtensions($fileExtension->folder()), $fileExtension)) {
            return;
        }

        throw new UserFriendlyException(__(
            'The :extension file format is not allowed. Accepted :folder formats: :extensions.',
            [
                'extension' => $fileExtension->value,
                'folder' => $fileExtension->folder(),
                'extensions' => self::getAcceptedExtensionsText($fileExtension),
            ]
        ));
    }

    private static function parseFileExtension(string $extension): FileExtension
    {
        try {
            return FileExtension::getEnumByString($extension);
        } catch (ValueError) {
            throw new UserFriendlyException(__(
                'The :extension file extension is not supported. Accepted formats: :extensions.',
                [
                    'extension' => $extension,
                    'extensions' => self::getAcceptedExtensionsText(),
                ]
            ));
        }
    }

    private static function containsExtension(array $extensions, FileExtension $fileExtension): bool
    {
        foreach ($extensions as $extension) {
            if ($extension instanceof FileExtension && cmprenum($extension, $fileExtension)) {
                return true;
            }

            if (is_string($extension) && cmprstr($extension, $fileExtension->value)) {
                return true;
            }
        }

        return false;
    }

    private static function ensureUploadImageDimensions(string $fileContents): void
    {
        $imageSize = @getimagesizefromstring($fileContents);

        if (! is_array($imageSize)) {
            throw new UserFriendlyException(__('The uploaded file is not a valid image.'));
        }

        if (
            is_more($imageSize[0], config('laravel-files.max_upload_image_side_pixels'))
            || is_more($imageSize[1], config('laravel-files.max_upload_image_side_pixels'))
        ) {
            throw new UserFriendlyException(__('The image resolution is too large. Maximum allowed side is :max_sidepx, actual resolution is :widthx:heightpx.', [
                'max_side' => config('laravel-files.max_upload_image_side_pixels'),
                'width' => $imageSize[0],
                'height' => $imageSize[1],
            ]));
        }
    }

    private static function prepareImageForStorage(string $fileContents, FileExtension $fileExtension): string
    {
        $image = Image::decodeBinary($fileContents);

        if (
            is_more($image->width(), config('laravel-files.max_image_side_pixels'))
            || is_more($image->height(), config('laravel-files.max_image_side_pixels'))
        ) {
            $image = is_more($image->width(), $image->height())
                ? $image->scale(width: config('laravel-files.max_image_side_pixels'))
                : $image->scale(height: config('laravel-files.max_image_side_pixels'));
        }

        return $image->encodeUsingFileExtension(
            $fileExtension->value,
            quality: config('laravel-files.image_cache_quality')
        )->toString();
    }

    private static function ensureFileSize(string $fileContents, int|float|string $maxSize): void
    {
        $actualSize = strlen($fileContents);

        if (is_more($actualSize, $maxSize)) {
            throw new UserFriendlyException(__('The file is too large. Maximum allowed size is :max_size, actual size is :actual_size.', [
                'actual_size' => bytes_conversion($actualSize),
                'max_size' => bytes_conversion((float) $maxSize),
            ]));
        }
    }

    private static function getAcceptedExtensionsText(?FileExtension $fileExtension = null): string
    {
        $extensions = FileExtension::acceptedExtensions($fileExtension?->folder());

        $values = [];

        foreach ($extensions as $extension) {
            $values[] = $extension instanceof FileExtension
                ? $extension->value
                : $extension;
        }

        if (empty($values)) {
            throw new UserFriendlyException(__('File uploads are not configured. Please contact the system administrator.'));
        }

        return implode(', ', $values);
    }

}
