<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Mantax559\LaravelFiles\Enums\FileSource;
use Mantax559\LaravelHelpers\Exceptions\UserFriendlyException;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class FileService
{
    private const FOLDER_CACHE = 'cache';

    private const FOLDER_DOCUMENT = 'document';

    private const FOLDER_FILE = 'file';

    private const FOLDER_IMAGE = 'image';

    private const FOLDER_SEEDER = 'seeder';

    private const EXTENSION_PDF = 'pdf';

    private const EXTENSION_WEBP = 'webp';

    private const array MIME_EXTENSIONS = [
        'application/pdf' => 'pdf',
        'image/avif' => 'avif',
        'image/gif' => 'gif',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/svg+xml' => 'svg',
        'image/webp' => 'webp',
    ];

    private const array WEBP_CONVERTIBLE_EXTENSIONS = [
        'jpeg',
        'jpg',
        'png',
        'webp',
    ];

    private array $deletedSeederFolders = [];

    private array $tempFiles = [];

    public function __construct(
        private FileSource $fileSource = FileSource::Manual
    ) {}

    public function save(string $file, string $folder): string
    {
        if (! is_file($file) && ! is_url($file)) {
            throw new UserFriendlyException(__('Bad file format!'));
        }

        $fileContents = self::readFileContents($file);
        $fileExtension = self::getFileExtension($file, $fileContents);

        if (self::isConvertibleToWebp($fileExtension)) {
            self::ensureImageUploadDimensions($fileContents);
            $fileContents = self::prepareImageForStorage($fileContents);
            $fileExtension = self::EXTENSION_WEBP;
        }

        self::ensureFileSize(
            $fileContents,
            config('laravel-files.max_file_size_bytes'),
            __('File is too large!')
        );

        $this->deleteSeederFolder($fileExtension, $folder);

        $filePath = self::path(
            $this->getStorageFolderPath($fileExtension, $folder),
            Str::uuid7()->toString().'.'.$fileExtension
        );

        Storage::disk(config('laravel-files.disk'))->put($filePath, $fileContents);

        $this->tempFiles[] = [
            'file_path' => $filePath,
            'is_upload' => true,
        ];

        return $filePath;
    }

    public function cacheImages(
        string $sourcePath,
        array $sizes,
        string|int|null $folder = null
    ): array {
        return collect($sizes)
            ->map(fn (array $size): string => $this->cacheImage(
                $sourcePath,
                $size['width'],
                $size['height'],
                $folder
            ))->all();
    }

    public function cacheImage(
        string $sourcePath,
        int $width,
        int $height,
        string|int|null $folder = null
    ): string {
        if (! Storage::disk(config('laravel-files.disk'))->exists($sourcePath)) {
            throw new RuntimeException(__('File does not exist: :path', ['path' => $sourcePath]));
        }

        $sourceInfo = pathinfo($sourcePath);
        $sourceExtension = self::getPathExtension($sourcePath);
        $cachePath = self::path(
            self::getCacheImageFolder($folder),
            slugify($sourceInfo['filename']).'-'.$width.'x'.$height.'.'.$sourceExtension
        );

        if (! Storage::disk(config('laravel-files.image_cache_disk'))->exists($cachePath)) {
            $image = Image::decodePath(Storage::disk(config('laravel-files.disk'))->path($sourcePath))->cover($width, $height);

            Storage::disk(config('laravel-files.image_cache_disk'))->put(
                $cachePath,
                $image->encodeUsingFileExtension($sourceExtension, quality: config('laravel-files.image_cache_quality'))
            );
        }

        return self::disk(config('laravel-files.image_cache_disk'))->url($cachePath);
    }

    public function rollbackFiles(): int
    {
        foreach ($this->tempFiles as $tempFile) {
            if ($tempFile['is_upload']) {
                $this->delete($tempFile['file_path']);
            } else {
                Storage::disk(config('laravel-files.disk'))->put($tempFile['file_path'], base64_decode($tempFile['file']));
            }
        }

        return count($this->tempFiles);
    }

    public static function transactionWithFileRollback(callable $callback, FileService $service): void
    {
        DB::beginTransaction();

        try {
            $callback();
            DB::commit();
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

    private function delete(?string $filePath, ?Model $model = null): bool
    {
        if (empty($filePath)) {
            return false;
        }

        if (! Storage::disk(config('laravel-files.disk'))->exists($filePath)) {
            return false;
        }

        if ($model) {
            Storage::disk(config('laravel-files.image_cache_disk'))->deleteDirectory(self::getCacheImageFolder($model->getKey()));
        }

        $this->tempFiles[] = [
            'file_path' => $filePath,
            'file' => base64_encode(Storage::disk(config('laravel-files.disk'))->get($filePath)),
            'is_upload' => false,
        ];

        return Storage::disk(config('laravel-files.disk'))->delete($filePath);
    }

    private function deleteSeederFolder(string $fileExtension, string $folder): void
    {
        if (! cmprenum($this->fileSource, FileSource::Seeder)) {
            return;
        }

        $folderPath = $this->getStorageFolderPath($fileExtension, $folder);

        if (
            collect($this->deletedSeederFolders)
                ->contains(fn (string $deletedFolder): bool => cmprstr($deletedFolder, $folderPath))
        ) {
            return;
        }

        Storage::disk(config('laravel-files.disk'))->deleteDirectory($folderPath);
        $this->deletedSeederFolders[] = $folderPath;
    }

    private function getStorageFolderPath(string $fileExtension, string $folder): string
    {
        $parts = [];

        if (cmprenum($this->fileSource, FileSource::Seeder)) {
            $parts[] = self::FOLDER_SEEDER;
        }

        $parts[] = self::getStorageFolder($fileExtension);
        $parts[] = slugify($folder);

        return self::path(...$parts);
    }

    private static function getCacheImageFolder(string|int|null $folder = null): string
    {
        $parts = [self::FOLDER_CACHE, self::FOLDER_IMAGE];

        if (filled($folder)) {
            $parts[] = slugify($folder);
        }

        return self::path(...$parts);
    }

    private static function getStorageFolder(string $fileExtension): string
    {
        if (self::isImageExtension($fileExtension)) {
            return self::FOLDER_IMAGE;
        }

        if (cmprstr($fileExtension, self::EXTENSION_PDF)) {
            return self::FOLDER_DOCUMENT;
        }

        return self::FOLDER_FILE;
    }

    private static function isImageExtension(string $fileExtension): bool
    {
        return collect(config('laravel-files.accept_image_mimes'))
            ->contains(fn (string $extension): bool => cmprstr($extension, $fileExtension));
    }

    private static function isConvertibleToWebp(string $fileExtension): bool
    {
        return collect(self::WEBP_CONVERTIBLE_EXTENSIONS)
            ->contains(fn (string $extension): bool => cmprstr($extension, $fileExtension));
    }

    private static function readFileContents(string $file): string
    {
        $handle = fopen($file, 'rb');

        if (! $handle) {
            throw new UserFriendlyException(__('Bad file format!'));
        }

        $contents = '';

        while (! feof($handle)) {
            $chunk = fread($handle, 1024 * 1024);

            if (! is_string($chunk)) {
                fclose($handle);

                throw new UserFriendlyException(__('Bad file format!'));
            }

            $contents .= $chunk;

            self::ensureFileSize(
                $contents,
                config('laravel-files.max_upload_file_size_bytes'),
                __('Uploaded file is too large!')
            );
        }

        fclose($handle);

        return $contents;
    }

    private static function getFileExtension(string $file, string $fileContents): string
    {
        try {
            return self::getPathExtension($file);
        } catch (UserFriendlyException) {
            return self::getFileExtensionFromMime($fileContents);
        }
    }

    private static function getPathExtension(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?: $path;
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (! empty($extension)) {
            return $extension;
        }

        throw new UserFriendlyException(__('Bad file format!'));
    }

    private static function getFileExtensionFromMime(string $fileContents): string
    {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($fileContents);

        if (is_string($mime) && isset(self::MIME_EXTENSIONS[$mime])) {
            return self::MIME_EXTENSIONS[$mime];
        }

        throw new UserFriendlyException(__('Bad file format!'));
    }

    private static function ensureImageUploadDimensions(string $fileContents): void
    {
        $imageSize = getimagesizefromstring($fileContents);

        if (! is_array($imageSize)) {
            throw new UserFriendlyException(__('Bad file format!'));
        }

        if (
            is_more($imageSize[0], config('laravel-files.max_upload_image_side_pixels'))
            || is_more($imageSize[1], config('laravel-files.max_upload_image_side_pixels'))
        ) {
            throw new UserFriendlyException(__('Image resolution is too large!'));
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
            self::EXTENSION_WEBP,
            quality: config('laravel-files.image_cache_quality')
        )->toString();
    }

    private static function ensureFileSize(string $fileContents, int|float|string $maxSize, string $message): void
    {
        if (is_more(strlen($fileContents), $maxSize)) {
            throw new UserFriendlyException($message);
        }
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
