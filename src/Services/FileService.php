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
use Mantax559\LaravelFiles\Enums\FileExtension;
use Mantax559\LaravelFiles\Enums\FileSource;
use Mantax559\LaravelHelpers\Exceptions\UserFriendlyException;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;
use ValueError;

class FileService
{
    private const FOLDER_CACHE = 'cache';

    private const FOLDER_SEEDER = 'seeder';

    private const array AVIF_CONVERTIBLE_EXTENSIONS = [
        FileExtension::Avif,
        FileExtension::Jpeg,
        FileExtension::Jpg,
        FileExtension::Png,
        FileExtension::Webp,
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
        self::ensureAcceptedFileExtension($fileExtension);

        if (self::isConvertibleToAvif($fileExtension)) {
            self::ensureImageUploadDimensions($fileContents);
            $fileContents = self::prepareImageForStorage($fileContents);
            $fileExtension = FileExtension::Avif;
        }

        self::ensureFileSize(
            $fileContents,
            config('laravel-files.max_file_size_bytes'),
            __('File is too large!')
        );

        $this->deleteSeederFolder($fileExtension, $folder);

        $filePath = self::path(
            $this->getStorageFolderPath($fileExtension, $folder),
            Str::uuid7()->toString().'.'.$fileExtension->value
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
            slugify($sourceInfo['filename']).'-'.$width.'x'.$height.'.'.$sourceExtension->value
        );

        if (! Storage::disk(config('laravel-files.image_cache_disk'))->exists($cachePath)) {
            $image = Image::decodePath(Storage::disk(config('laravel-files.disk'))->path($sourcePath))->cover($width, $height);

            Storage::disk(config('laravel-files.image_cache_disk'))->put(
                $cachePath,
                $image->encodeUsingFileExtension($sourceExtension->value, quality: config('laravel-files.image_cache_quality'))
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

    private function deleteSeederFolder(FileExtension $fileExtension, string $folder): void
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

    private static function getCacheImageFolder(string|int|null $folder = null): string
    {
        $parts = [self::FOLDER_CACHE, FileExtension::FOLDER_IMAGE];

        if (filled($folder)) {
            $parts[] = slugify($folder);
        }

        return self::path(...$parts);
    }

    private static function isConvertibleToAvif(FileExtension $fileExtension): bool
    {
        return collect(self::AVIF_CONVERTIBLE_EXTENSIONS)
            ->contains(fn (FileExtension $extension): bool => cmprenum($extension, $fileExtension));
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
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (! empty($extension)) {
            return self::getEnumExtension($extension);
        }

        throw new UserFriendlyException(__('Bad file format!'));
    }

    private static function getFileExtensionFromMime(string $fileContents): FileExtension
    {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($fileContents);

        if (is_string($mime)) {
            try {
                return FileExtension::getByMimeType($mime);
            } catch (ValueError) {
                throw new UserFriendlyException(__('Bad file format!'));
            }
        }

        throw new UserFriendlyException(__('Bad file format!'));
    }

    private static function ensureAcceptedFileExtension(FileExtension $fileExtension): void
    {
        if (self::containsExtension(self::getAcceptedExtensions($fileExtension), $fileExtension)) {
            return;
        }

        throw new UserFriendlyException(__('Bad file format!'));
    }

    private static function getAcceptedExtensions(FileExtension $fileExtension): array
    {
        return self::normalizeExtensions(config('laravel-files.accept_'.$fileExtension->folder().'_extensions'));
    }

    private static function normalizeExtensions(array $extensions): array
    {
        return collect($extensions)
            ->map(fn (FileExtension|string $extension): FileExtension => self::normalizeExtension($extension))
            ->all();
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
            return FileExtension::getEnumByString(strtolower($extension));
        } catch (ValueError) {
            throw new UserFriendlyException(__('Bad file format!'));
        }
    }

    private static function containsExtension(array $extensions, FileExtension $fileExtension): bool
    {
        return collect($extensions)
            ->contains(fn (FileExtension $extension): bool => cmprenum($extension, $fileExtension));
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
            FileExtension::Avif->value,
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
