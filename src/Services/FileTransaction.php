<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Services;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class FileTransaction
{
    private const string FOLDER_ROLLBACK_TEMP = '.rollback-temp';

    private readonly string $folder;

    private array $uploaded = [];

    private array $deleted = [];

    private array $deletedDirectories = [];

    public function __construct()
    {
        $this->folder = self::path(self::FOLDER_ROLLBACK_TEMP, Str::uuid7()->toString());
    }

    public static function run(callable $callback): mixed
    {
        $transaction = new self;

        try {
            $result = DB::transaction(static fn (): mixed => $callback($transaction));
            $transaction->commit();

            return $result;
        } catch (Throwable $exception) {
            try {
                $transaction->rollback();
            } catch (Throwable $rollbackException) {
                Log::error('File transaction rollback failed.', [
                    'exception' => $rollbackException,
                ]);
            }

            throw $exception;
        }
    }

    public function addUploaded(string $path): void
    {
        $this->uploaded[] = $path;
    }

    public function addDeleted(string $path): void
    {
        $tempPath = $this->tempPath($path);

        if (! self::disk()->move($path, $tempPath)) {
            throw new RuntimeException(__('The file could not be moved to rollback storage.'));
        }

        $this->deleted[] = [
            'path' => $path,
            'temp_path' => $tempPath,
        ];
    }

    public function addDeletedDirectory(string $path): void
    {
        foreach ($this->deletedDirectories as $deletedDirectory) {
            if (cmprstr($deletedDirectory, $path)) {
                return;
            }
        }

        foreach (self::disk()->allFiles($path) as $filePath) {
            $this->addDeleted($filePath);
        }

        self::deleteDirectory($path);
        $this->deletedDirectories[] = $path;
    }

    public function commit(): void
    {
        foreach ($this->deleted as $file) {
            self::deleteFile($file['temp_path']);
        }

        self::deleteDirectory($this->folder);
        $this->clear();
    }

    public function rollback(): void
    {
        foreach ($this->uploaded as $path) {
            self::deleteFile($path);
        }

        foreach ($this->deleted as $file) {
            if (self::disk()->exists($file['path'])) {
                self::deleteFile($file['path']);
            }

            if (! self::disk()->move($file['temp_path'], $file['path'])) {
                throw new RuntimeException(__('The file could not be restored from rollback storage.'));
            }
        }

        self::deleteDirectory($this->folder);
        $this->clear();
    }

    private function tempPath(string $path): string
    {
        return self::path($this->folder, $path);
    }

    private function clear(): void
    {
        $this->uploaded = [];
        $this->deleted = [];
        $this->deletedDirectories = [];
    }

    private static function deleteFile(string $path): void
    {
        if (! self::disk()->delete($path) && self::disk()->exists($path)) {
            throw new RuntimeException(__('The file could not be deleted.'));
        }
    }

    private static function deleteDirectory(string $path): void
    {
        if (! self::disk()->deleteDirectory($path) && self::disk()->directoryExists($path)) {
            throw new RuntimeException(__('The file directory could not be deleted.'));
        }
    }

    private static function path(string ...$parts): string
    {
        return implode('/', $parts);
    }

    private static function disk(): FilesystemAdapter
    {
        return Storage::disk(config('laravel-files.disk'));
    }
}
