<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        $this->folder = FileStorage::path(self::FOLDER_ROLLBACK_TEMP, Str::uuid7()->toString());
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

        if (! FileStorage::disk(config('laravel-files.disk'))->move($path, $tempPath)) {
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

        foreach (FileStorage::disk(config('laravel-files.disk'))->allFiles($path) as $filePath) {
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
            if (FileStorage::disk(config('laravel-files.disk'))->exists($file['path'])) {
                self::deleteFile($file['path']);
            }

            if (! FileStorage::disk(config('laravel-files.disk'))->move($file['temp_path'], $file['path'])) {
                throw new RuntimeException(__('The file could not be restored from rollback storage.'));
            }
        }

        self::deleteDirectory($this->folder);
        $this->clear();
    }

    private function tempPath(string $path): string
    {
        return FileStorage::path($this->folder, $path);
    }

    private function clear(): void
    {
        $this->uploaded = [];
        $this->deleted = [];
        $this->deletedDirectories = [];
    }

    private static function deleteFile(string $path): void
    {
        if (! FileStorage::deleteFile(config('laravel-files.disk'), $path)) {
            throw new RuntimeException(__('The file could not be deleted.'));
        }
    }

    private static function deleteDirectory(string $path): void
    {
        if (! FileStorage::deleteDirectory(config('laravel-files.disk'), $path)) {
            throw new RuntimeException(__('The file directory could not be deleted.'));
        }
    }
}
