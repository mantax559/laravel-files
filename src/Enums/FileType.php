<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Enums;

use Mantax559\LaravelHelpers\Traits\EnumTrait;

enum FileType: string
{
    use EnumTrait;

    case Image = 'image';
    case Logo = 'logo';
    case Document = 'document';
}
