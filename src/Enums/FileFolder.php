<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Enums;

use Mantax559\LaravelHelpers\Traits\EnumTrait;

enum FileFolder: string
{
    use EnumTrait;

    case Document = 'document';
    case Image = 'image';
    case Seeder = 'seeder';
}
