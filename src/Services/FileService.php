<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Mantax559\LaravelFiles\Enums\FileExtension;
use Mantax559\LaravelFiles\Enums\FileSource;
use Mantax559\LaravelFiles\Enums\FileType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class FileService
{
    private array $tempFiles = [];

    private string $filePath = '';

    public function __construct(
        private FileType $fileType = FileType::Image,
        private FileSource $fileSource = FileSource::Manual,
        private FileExtension $fileExtension = FileExtension::Png
    ) {
        if (cmprenum($this->fileSource, FileSource::Seeder)) {
            $this->filePath .= 'seeder/';
        }

        $this->filePath .= match ($this->fileExtension) {
            FileExtension::Gif, FileExtension::Jpeg, FileExtension::Jpg, FileExtension::Png, FileExtension::Webp => 'image/',
            FileExtension::Pdf => 'document/',
            default => 'file/',
        };

        $this->filePath .= $this->fileType->value.'/';

        if (cmprenum($this->fileSource, FileSource::Seeder)) {
            Storage::disk(config('laravel-files.disk'))->deleteDirectory($this->filePath);
        }
    }

    public function save(string $file, string $folder): string
    {
        $filePath = $this->filePath.Str::slug($folder).'/'.Str::random(32).'.'.$this->fileExtension->value;

        Storage::disk(config('laravel-files.disk'))->put($filePath, file_get_contents($file));

        $this->tempFiles[] = [
            'file_path' => $filePath,
            'is_upload' => true,
        ];

        return $filePath;
    }

    public function cacheImages(
        string $sourcePath,
        array $sizes,
        ?FileType $fileType = null,
        ?string $filename = null,
        string|int|null $folder = null
    ): array {
        return collect($sizes)
            ->map(fn (array $size): array => $this->cacheImage(
                $sourcePath,
                $size['width'],
                $size['height'],
                $fileType,
                $filename,
                $folder
            ))->all();
    }

    public function cacheImage(
        string $sourcePath,
        int $width,
        int $height,
        ?FileType $fileType = null,
        ?string $filename = null,
        string|int|null $folder = null
    ): array {
        $disk = Storage::disk(config('laravel-files.disk'));

        if (! $disk->exists($sourcePath)) {
            throw new \RuntimeException(__('File does not exist: :path', ['path' => $sourcePath]));
        }

        $sourceInfo = pathinfo($sourcePath);
        $cacheFolder = self::getCacheImageFolder($fileType, $folder);
        $cachePath = $cacheFolder.'/'.Str::slug($filename ?? $sourceInfo['filename']).'-'.$width.'x'.$height.'.'.$sourceInfo['extension'];

        if (! $disk->exists($cachePath)) {
            $image = Image::decodePath($disk->path($sourcePath))->cover($width, $height);

            $disk->put(
                $cachePath,
                $image->encodeUsingFileExtension($sourceInfo['extension'], quality: config('laravel-files.image_cache_quality'))
            );
        }

        return [
            'label' => $width.'x'.$height,
            'path' => $cachePath,
            'url' => asset('storage/'.$cachePath),
            'width' => $width,
            'height' => $height,
        ];
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
        if (blank($filePath)) {
            return false;
        }

        if (! Storage::disk(config('laravel-files.disk'))->exists($filePath)) {
            return false;
        }

        if ($model) {
            Storage::disk(config('laravel-files.disk'))->deleteDirectory(self::getCacheImageFolder($this->fileType, $model->getKey()));
        }

        $this->tempFiles[] = [
            'file_path' => $filePath,
            'file' => base64_encode(Storage::disk(config('laravel-files.disk'))->get($filePath)),
            'is_upload' => false,
        ];

        return Storage::disk(config('laravel-files.disk'))->delete($filePath);
    }

    private static function getCacheImageFolder(?FileType $fileType = null, string|int|null $folder = null): string
    {
        $parts = [config('laravel-files.image_cache_folder')];

        if ($fileType) {
            $parts[] = $fileType->value;
        }

        if (filled($folder)) {
            $parts[] = Str::slug($folder);
        }

        return implode('/', $parts);
    }
}
