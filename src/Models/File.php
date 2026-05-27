<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mantax559\LaravelFiles\Enums\FileExtension;
use Mantax559\LaravelFiles\Enums\FileSource;

class File extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'path',
        'size',
        'extension',
        'source',
    ];

    protected $casts = [
        'extension' => FileExtension::class,
        'size' => 'integer',
        'source' => FileSource::class,
    ];

    public $timestamps = true;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('laravel-files.table'));
    }
}
