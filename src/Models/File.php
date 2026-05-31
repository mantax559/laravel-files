<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Mantax559\LaravelFiles\Enums\FileExtension;
use Mantax559\LaravelFiles\Services\FileStorage;
use Mantax559\LaravelObservability\Traits\ActivityTrait;

class File extends Model
{
    use ActivityTrait;
    use HasUuids;

    protected $fillable = [
        'folder',
        'extension',
        'size',
    ];

    protected $casts = [
        'extension' => FileExtension::class,
    ];

    public $timestamps = true;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('laravel-files.table'));
    }

    public function getPathAttribute(): string
    {
        return FileStorage::path($this->folder, $this->getKey().'.'.$this->extension->value);
    }
}
