<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Enums;

use Mantax559\LaravelHelpers\Traits\EnumTrait;

enum FileExtension: string
{
    use EnumTrait;

    case Csv = 'csv';
    case Doc = 'doc';
    case Docx = 'docx';
    case Gif = 'gif';
    case Jpeg = 'jpeg';
    case Jpg = 'jpg';
    case Pdf = 'pdf';
    case Png = 'png';
    case Txt = 'txt';
    case Webp = 'webp';
    case Xls = 'xls';
    case Xlsx = 'xlsx';
    case Xml = 'xml';
    case Zip = 'zip';
}
