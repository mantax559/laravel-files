<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Services;

use finfo;
use Illuminate\Database\QueryException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Mantax559\LaravelFiles\Enums\FileExtension;
use Mantax559\LaravelFiles\Enums\FileSource;
use Mantax559\LaravelFiles\Models\File;
use Mantax559\LaravelHelpers\Exceptions\UserFriendlyException;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;
use ValueError;

class FileService
{
    private const string FOLDER_CACHE = 'cache';

    private const string FOLDER_SEEDER = 'seeder';

    private const string CACHE_AUTO_DIMENSION = 'auto';

    private array $deletedSeederFolders = [];

    private array $tempFiles = [];

    public function __construct(
        private FileSource $fileSource = FileSource::Manual
    ) {}

    public function save(string $file, string $folder): string
    {
        if (! is_file($file) && ! is_url($file)) {
            throw new UserFriendlyException(__('The file could not be read. Provide a valid local path or URL.'));
        }

        $fileContents = self::readFileContents($file);
        $fileExtension = self::getFileExtension($file, $fileContents);
        self::ensureAcceptedFileExtension($fileExtension);

        if ($fileExtension->isConvertibleToAvif()) {
            self::ensureImageUploadDimensions($fileContents);
            $fileContents = self::prepareImageForStorage($fileContents);
            $fileExtension = FileExtension::Avif;
        }

        self::ensureFileSize(
            $fileContents,
            config('laravel-files.max_file_size_bytes'),
            'The stored file is too large. Maximum allowed size is :max_size, actual size is :actual_size.'
        );

        $this->deleteSeederFolder($fileExtension, $folder);

        $filePath = self::path(
            $this->getStorageFolderPath($fileExtension, $folder),
            Str::uuid7()->toString().'.'.$fileExtension->value
        );

        if (! self::putFile(config('laravel-files.disk'), $filePath, $fileContents)) {
            throw new UserFriendlyException(__('The file could not be stored. Please try again.'));
        }

        $this->tempFiles[] = [
            'file_path' => $filePath,
            'is_upload' => true,
        ];

        return $filePath;
    }

    public static function cacheImage(
        string $sourcePath,
        ?int $width = null,
        ?int $height = null,
        string|int|array|null $folderSource = null
    ): string {
        if (! Storage::disk(config('laravel-files.disk'))->exists($sourcePath)) {
            throw new RuntimeException(__('File does not exist: :path', ['path' => $sourcePath]));
        }

        $sourceInfo = pathinfo($sourcePath);
        self::getPathExtension($sourcePath);
        $coverImage = ! empty($width) && ! empty($height);
        $cachePath = self::getCacheImagePath($sourceInfo['filename'], $width, $height, $folderSource);

        if (Storage::disk(config('laravel-files.image_cache_disk'))->exists($cachePath)) {
            return self::disk(config('laravel-files.image_cache_disk'))->url($cachePath);
        }

        $image = Image::decodePath(Storage::disk(config('laravel-files.disk'))->path($sourcePath));

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

        if (! Storage::disk(config('laravel-files.image_cache_disk'))->exists($cachePath)) {
            if ($coverImage) {
                $image = $image->cover($width, $height);
            }

            if (! self::putFile(
                config('laravel-files.image_cache_disk'),
                $cachePath,
                $image->encodeUsingFileExtension(FileExtension::Avif->value, quality: config('laravel-files.image_cache_quality'))->toString()
            )) {
                throw new RuntimeException(__('The image cache could not be stored.'));
            }
        }

        return self::disk(config('laravel-files.image_cache_disk'))->url($cachePath);
    }

    private function rollbackFiles(): int
    {
        foreach ($this->tempFiles as $tempFile) {
            if ($tempFile['is_upload']) {
                self::deleteFile(config('laravel-files.disk'), $tempFile['file_path']);
            } else {
                self::putFile(config('laravel-files.disk'), $tempFile['file_path'], base64_decode($tempFile['file']));
            }
        }

        $rollbackCount = count($this->tempFiles);
        $this->tempFiles = [];

        return $rollbackCount;
    }

    public function deleteModelFiles(File $file): bool
    {
        if (empty($file->path)) {
            return false;
        }

        if (! Storage::disk(config('laravel-files.disk'))->exists($file->path)) {
            Log::error('File delete failed.', [
                'disk' => config('laravel-files.disk'),
                'path' => $file->path,
            ]);

            return false;
        }

        $this->tempFiles[] = [
            'file_path' => $file->path,
            'file' => base64_encode(Storage::disk(config('laravel-files.disk'))->get($file->path)),
            'is_upload' => false,
        ];

        if (! self::deleteFile(config('laravel-files.disk'), $file->path)) {
            return false;
        }

        if (self::deleteDirectory(config('laravel-files.image_cache_disk'), self::getCacheImageFolder($file->getKey()))) {
            return true;
        }

        $this->rollbackFiles();

        return false;
    }

    public static function transactionWithFileRollback(callable $callback, FileService $service): mixed
    {
        DB::beginTransaction();

        try {
            $result = $callback();

            if (is_bool($result) && ! $result) {
                DB::rollBack();
                $service->rollbackFiles();

                return false;
            }

            DB::commit();

            return $result;
        } catch (QueryException $exception) {
            DB::rollBack();
            $service->rollbackFiles();

            throw new QueryException(
                $exception->getConnectionName(),
                $exception->getSql(),
                $exception->getBindings(),
                $exception->getPrevious()
            );
        } catch (Throwable $exception) {
            DB::rollBack();
            $service->rollbackFiles();

            throw $exception;
        }
    }

    public static function open(string $filePath, string $headerContentType): BinaryFileResponse
    {
        return response()->file(Storage::disk(config('laravel-files.disk'))->path($filePath), ['Content-Type' => $headerContentType]);
    }

    public static function download(string $filePath): BinaryFileResponse
    {
        return response()->download(Storage::disk(config('laravel-files.disk'))->path($filePath));
    }

    private function deleteSeederFolder(FileExtension $fileExtension, string $folder): void
    {
        if (! cmprenum($this->fileSource, FileSource::Seeder)) {
            return;
        }

        $folderPath = $this->getStorageFolderPath($fileExtension, $folder);

        foreach ($this->deletedSeederFolders as $deletedSeederFolder) {
            if (cmprstr($deletedSeederFolder, $folderPath)) {
                return;
            }
        }

        self::deleteDirectory(config('laravel-files.disk'), $folderPath);
        $this->deletedSeederFolders[] = $folderPath;
    }

    private function getStorageFolderPath(FileExtension $fileExtension, string $folder): string
    {
        $parts = [];

        if (cmprenum($this->fileSource, FileSource::Seeder)) {
            $parts[] = self::FOLDER_SEEDER;
        }

        $parts[] = $fileExtension->folder();
        $parts[] = slugify($folder);

        return self::path(...$parts);
    }

    private static function getCacheImageFolder(string|int|array|null $folderSource = null): string
    {
        $parts = [self::FOLDER_CACHE, FileExtension::FOLDER_IMAGE];

        foreach (is_array($folderSource) ? $folderSource : [$folderSource] as $folder) {
            if (filled($folder)) {
                $parts[] = slugify($folder);
            }
        }

        return self::path(...$parts);
    }

    private static function getCacheImagePath(string $filename, ?int $width, ?int $height, string|int|array|null $folderSource = null): string
    {
        return self::path(
            self::getCacheImageFolder($folderSource),
            slugify($filename).'-'.($width ?? self::CACHE_AUTO_DIMENSION).'x'.($height ?? self::CACHE_AUTO_DIMENSION).'.'.FileExtension::Avif->value
        );
    }

    private static function putFile(string $disk, string $filePath, string $fileContents): bool
    {
        try {
            $stored = self::disk($disk)->put($filePath, $fileContents);
        } catch (Throwable $exception) {
            Log::error('File write failed.', [
                'disk' => $disk,
                'path' => $filePath,
                'exception' => $exception,
            ]);

            return false;
        }

        if (! $stored) {
            Log::error('File write failed.', [
                'disk' => $disk,
                'path' => $filePath,
            ]);
        }

        return $stored;
    }

    private static function deleteFile(string $disk, string $filePath): bool
    {
        try {
            $deleted = self::disk($disk)->delete($filePath);
        } catch (Throwable $exception) {
            Log::error('File delete failed.', [
                'disk' => $disk,
                'path' => $filePath,
                'exception' => $exception,
            ]);

            return false;
        }

        if (! $deleted) {
            Log::error('File delete failed.', [
                'disk' => $disk,
                'path' => $filePath,
            ]);
        }

        return $deleted;
    }

    private static function deleteDirectory(string $disk, string $folderPath): bool
    {
        try {
            if (! self::disk($disk)->directoryExists($folderPath)) {
                return true;
            }

            $deleted = self::disk($disk)->deleteDirectory($folderPath);
        } catch (Throwable $exception) {
            Log::error('File directory delete failed.', [
                'disk' => $disk,
                'path' => $folderPath,
                'exception' => $exception,
            ]);

            return false;
        }

        if (! $deleted) {
            Log::error('File directory delete failed.', [
                'disk' => $disk,
                'path' => $folderPath,
            ]);
        }

        return $deleted;
    }

    private static function readFileContents(string $file): string
    {
        $handle = @fopen($file, 'rb');

        if (! $handle) {
            throw new UserFriendlyException(__('The file could not be opened. Please try uploading it again.'));
        }

        $contents = '';

        while (! feof($handle)) {
            $chunk = fread($handle, 1024 * 1024);
            $contents .= $chunk;

            self::ensureFileSize(
                $contents,
                config('laravel-files.max_upload_file_size_bytes'),
                'The uploaded file is too large. Maximum allowed size is :max_size, actual size is :actual_size.'
            );
        }

        fclose($handle);

        return $contents;
    }

    private static function getFileExtension(string $file, string $fileContents): FileExtension
    {
        try {
            return self::getPathExtension($file);
        } catch (UserFriendlyException) {
            return self::getFileExtensionFromMime($fileContents);
        }
    }

    private static function getPathExtension(string $path): FileExtension
    {
        $path = parse_url($path, PHP_URL_PATH) ?: $path;
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        if (! empty($extension)) {
            return self::getEnumExtension($extension);
        }

        throw new UserFriendlyException(__('The file extension could not be detected.'));
    }

    private static function getFileExtensionFromMime(string $fileContents): FileExtension
    {
        $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->buffer($fileContents);

        try {
            return FileExtension::getByMimeType($mime);
        } catch (ValueError) {
            throw new UserFriendlyException(__(
                'The detected MIME type :mime is not supported. Accepted formats: :extensions.',
                [
                    'mime' => $mime,
                    'extensions' => self::getAcceptedExtensionsText(),
                ]
            ));
        }
    }

    private static function ensureAcceptedFileExtension(FileExtension $fileExtension): void
    {
        if (self::containsExtension(self::getAcceptedExtensions($fileExtension), $fileExtension)) {
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

    private static function getAcceptedExtensions(FileExtension $fileExtension): array
    {
        return self::normalizeExtensions(config('laravel-files.accept_'.$fileExtension->folder().'_extensions'));
    }

    private static function normalizeExtensions(array $extensions): array
    {
        $normalizedExtensions = [];

        foreach ($extensions as $extension) {
            $normalizedExtensions[] = self::normalizeExtension($extension);
        }

        return $normalizedExtensions;
    }

    private static function normalizeExtension(FileExtension|string $extension): FileExtension
    {
        if ($extension instanceof FileExtension) {
            return $extension;
        }

        return self::getEnumExtension($extension);
    }

    private static function getEnumExtension(string $extension): FileExtension
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
            if (cmprenum($extension, $fileExtension)) {
                return true;
            }
        }

        return false;
    }

    private static function ensureImageUploadDimensions(string $fileContents): void
    {
        $imageSize = @getimagesizefromstring($fileContents);

        if (! is_array($imageSize)) {
            throw new UserFriendlyException(__('The uploaded file is not a valid image.'));
        }

        if (
            is_more($imageSize[0], config('laravel-files.max_upload_image_side_pixels'))
            || is_more($imageSize[1], config('laravel-files.max_upload_image_side_pixels'))
        ) {
            throw new UserFriendlyException(__(
                'The image resolution is too large. Maximum allowed side is :max_sidepx, actual resolution is :widthx:heightpx.',
                [
                    'max_side' => config('laravel-files.max_upload_image_side_pixels'),
                    'width' => $imageSize[0],
                    'height' => $imageSize[1],
                ]
            ));
        }
    }

    private static function prepareImageForStorage(string $fileContents): string
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
            FileExtension::Avif->value,
            quality: config('laravel-files.image_cache_quality')
        )->toString();
    }

    private static function ensureFileSize(string $fileContents, int|float|string $maxSize, string $message): void
    {
        $actualSize = strlen($fileContents);

        if (is_more($actualSize, $maxSize)) {
            throw new UserFriendlyException(__($message, [
                'actual_size' => bytes_conversion($actualSize),
                'max_size' => bytes_conversion((float) $maxSize),
            ]));
        }
    }

    private static function getAcceptedExtensionsText(?FileExtension $fileExtension = null): string
    {
        $extensions = $fileExtension
            ? self::getAcceptedExtensions($fileExtension)
            : [
                ...self::normalizeExtensions(config('laravel-files.accept_archive_extensions')),
                ...self::normalizeExtensions(config('laravel-files.accept_audio_extensions')),
                ...self::normalizeExtensions(config('laravel-files.accept_document_extensions')),
                ...self::normalizeExtensions(config('laravel-files.accept_image_extensions')),
                ...self::normalizeExtensions(config('laravel-files.accept_video_extensions')),
                ...self::normalizeExtensions(config('laravel-files.accept_file_extensions')),
            ];

        $values = [];

        foreach ($extensions as $extension) {
            $values[] = $extension->value;
        }

        return implode(', ', $values);
    }

    private static function path(string ...$parts): string
    {
        return implode('/', $parts);
    }

    private static function disk(string $disk): FilesystemAdapter
    {
        return Storage::disk($disk);
    }
}
