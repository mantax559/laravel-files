<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Mantax559\LaravelFiles\Enums\FileExtension;
use Mantax559\LaravelFiles\Enums\FileSource;
use Mantax559\LaravelFiles\Services\FileService;

class File extends Model
{
    use HasUuids;

    protected $fillable = [
        'path',
        'extension',
        'source',
        'size',
    ];

    protected $casts = [
        'extension' => FileExtension::class,
        'source' => FileSource::class,
    ];

    public $timestamps = true;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('laravel-files.table'));
    }

    public function delete(): ?bool
    {
        $fileService = new FileService;

        return FileService::transactionWithFileRollback(function () use ($fileService): ?bool {
            if (! $fileService->deleteModelFiles($this)) {
                return false;
            }

            $deleted = parent::delete();

            if (! $deleted) {
                $fileService->rollbackFiles();
            }

            return $deleted;
        }, $fileService);
    }
}
