<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Enums;

use Mantax559\LaravelHelpers\Traits\EnumTrait;

enum FileExtension: string
{
    use EnumTrait;

    public const string FOLDER_ARCHIVE = 'archive';

    public const string FOLDER_AUDIO = 'audio';

    public const string FOLDER_DOCUMENT = 'document';

    public const string FOLDER_IMAGE = 'image';

    public const string FOLDER_VIDEO = 'video';

    public const string FOLDER_FILE = 'file';

    public const self STORED_IMAGE_EXTENSION = self::Avif;

    case SevenZip = '7z';
    case Apng = 'apng';
    case Avi = 'avi';
    case Avif = 'avif';
    case Csv = 'csv';
    case Doc = 'doc';
    case Docx = 'docx';
    case Flac = 'flac';
    case Gif = 'gif';
    case Gz = 'gz';
    case Jfif = 'jfif';
    case Jpeg = 'jpeg';
    case Jpg = 'jpg';
    case Json = 'json';
    case M4a = 'm4a';
    case Mkv = 'mkv';
    case Mov = 'mov';
    case Mp3 = 'mp3';
    case Mp4 = 'mp4';
    case Ods = 'ods';
    case Odt = 'odt';
    case Ogg = 'ogg';
    case Pdf = 'pdf';
    case Pjpeg = 'pjpeg';
    case Pjp = 'pjp';
    case Png = 'png';
    case Ppt = 'ppt';
    case Pptx = 'pptx';
    case Rar = 'rar';
    case Rtf = 'rtf';
    case Svg = 'svg';
    case Tar = 'tar';
    case Txt = 'txt';
    case Wav = 'wav';
    case Webm = 'webm';
    case Webp = 'webp';
    case Xls = 'xls';
    case Xlsx = 'xlsx';
    case Xml = 'xml';
    case Zip = 'zip';

    public function folder(): string
    {
        return match ($this) {
            self::SevenZip,
            self::Gz,
            self::Rar,
            self::Tar,
            self::Zip => self::FOLDER_ARCHIVE,
            self::Flac,
            self::M4a,
            self::Mp3,
            self::Ogg,
            self::Wav => self::FOLDER_AUDIO,
            self::Csv,
            self::Doc,
            self::Docx,
            self::Ods,
            self::Odt,
            self::Pdf,
            self::Ppt,
            self::Pptx,
            self::Rtf,
            self::Txt,
            self::Xls,
            self::Xlsx => self::FOLDER_DOCUMENT,
            self::Apng,
            self::Avif,
            self::Gif,
            self::Jfif,
            self::Jpeg,
            self::Jpg,
            self::Pjp,
            self::Pjpeg,
            self::Png,
            self::Svg,
            self::Webp => self::FOLDER_IMAGE,
            self::Avi,
            self::Mkv,
            self::Mov,
            self::Mp4,
            self::Webm => self::FOLDER_VIDEO,
            default => self::FOLDER_FILE,
        };
    }

    public function contentType(): string
    {
        return match ($this) {
            self::SevenZip => 'application/x-7z-compressed',
            self::Apng => 'image/apng',
            self::Avi => 'video/x-msvideo',
            self::Avif => 'image/avif',
            self::Csv => 'text/csv',
            self::Doc => 'application/msword',
            self::Docx => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            self::Flac => 'audio/flac',
            self::Gif => 'image/gif',
            self::Gz => 'application/gzip',
            self::Jfif,
            self::Jpeg,
            self::Jpg,
            self::Pjp,
            self::Pjpeg => 'image/jpeg',
            self::Json => 'application/json',
            self::M4a => 'audio/mp4',
            self::Mkv => 'video/x-matroska',
            self::Mov => 'video/quicktime',
            self::Mp3 => 'audio/mpeg',
            self::Mp4 => 'video/mp4',
            self::Ods => 'application/vnd.oasis.opendocument.spreadsheet',
            self::Odt => 'application/vnd.oasis.opendocument.text',
            self::Ogg => 'audio/ogg',
            self::Pdf => 'application/pdf',
            self::Png => 'image/png',
            self::Ppt => 'application/vnd.ms-powerpoint',
            self::Pptx => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            self::Rar => 'application/vnd.rar',
            self::Rtf => 'application/rtf',
            self::Svg => 'image/svg+xml',
            self::Tar => 'application/x-tar',
            self::Txt => 'text/plain',
            self::Wav => 'audio/wav',
            self::Webm => 'video/webm',
            self::Webp => 'image/webp',
            self::Xls => 'application/vnd.ms-excel',
            self::Xlsx => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            self::Xml => 'application/xml',
            self::Zip => 'application/zip',
        };
    }

    public function isImage(): bool
    {
        return cmprstr($this->folder(), self::FOLDER_IMAGE);
    }

    public function storageImageExtension(): self
    {
        if ($this->isConvertibleToStoredImage()) {
            return self::STORED_IMAGE_EXTENSION;
        }

        return $this;
    }

    public function isConvertibleToStoredImage(): bool
    {
        return match ($this) {
            self::Avif,
            self::Jfif,
            self::Jpeg,
            self::Jpg,
            self::Pjp,
            self::Pjpeg,
            self::Png,
            self::Webp => true,
            default => false,
        };
    }

    public static function acceptedExtensions(?string $folder = null): array
    {
        if (! is_string($folder)) {
            return config('laravel-files.accept_extensions');
        }

        $filteredExtensions = [];

        foreach (config('laravel-files.accept_extensions') as $extension) {
            if (cmprstr($extension->folder(), $folder)) {
                $filteredExtensions[] = $extension;
            }
        }

        return $filteredExtensions;
    }
}
