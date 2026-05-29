<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Mantax559\LaravelFiles\Enums\FileExtension;

class File extends Model
{
    use HasUuids;

    protected $fillable = [
        'path',
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
}
