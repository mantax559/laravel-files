<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Enums;

use Mantax559\LaravelHelpers\Traits\EnumTrait;

enum FileSource: string
{
    use EnumTrait;

    case Seeder = 'seeder';
    case Manual = 'manual';
}
