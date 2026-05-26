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
        'user_id',
        'size_bits',
        'extension',
        'disk',
        'original_name',
        'mime_type',
        'checksum',
    ];

    protected $casts = [
        'extension' => FileExtension::class,
        'size_bits' => 'integer',
    ];

    public $timestamps = true;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('laravel-files.table'));
    }
}
