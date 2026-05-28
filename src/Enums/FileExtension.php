<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Enums;

use Mantax559\LaravelHelpers\Traits\EnumTrait;
use ValueError;

enum FileExtension: string
{
    use EnumTrait;

    public const string FOLDER_ARCHIVE = 'archive';

    public const string FOLDER_AUDIO = 'audio';

    public const string FOLDER_DOCUMENT = 'document';

    public const string FOLDER_IMAGE = 'image';

    public const string FOLDER_VIDEO = 'video';

    public const string FOLDER_FILE = 'file';

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

    public static function archiveExtensions(): array
    {
        return [
            self::SevenZip,
            self::Gz,
            self::Rar,
            self::Tar,
            self::Zip,
        ];
    }

    public static function audioExtensions(): array
    {
        return [
            self::Flac,
            self::M4a,
            self::Mp3,
            self::Ogg,
            self::Wav,
        ];
    }

    public static function documentExtensions(): array
    {
        return [
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
            self::Xlsx,
        ];
    }

    public static function imageExtensions(): array
    {
        return [
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
            self::Webp,
        ];
    }

    public static function videoExtensions(): array
    {
        return [
            self::Avi,
            self::Mkv,
            self::Mov,
            self::Mp4,
            self::Webm,
        ];
    }

    public static function fileExtensions(): array
    {
        return [
            self::Json,
            self::Xml,
        ];
    }

    public static function getByMimeType(string $mimeType): self
    {
        return [
            'application/epub+zip' => self::Zip,
            'application/gzip' => self::Gz,
            'application/json' => self::Json,
            'application/msword' => self::Doc,
            'application/pdf' => self::Pdf,
            'application/rtf' => self::Rtf,
            'application/vnd.ms-excel' => self::Xls,
            'application/vnd.ms-powerpoint' => self::Ppt,
            'application/vnd.oasis.opendocument.spreadsheet' => self::Ods,
            'application/vnd.oasis.opendocument.text' => self::Odt,
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => self::Pptx,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => self::Xlsx,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => self::Docx,
            'application/vnd.rar' => self::Rar,
            'application/x-7z-compressed' => self::SevenZip,
            'application/x-rar-compressed' => self::Rar,
            'application/x-tar' => self::Tar,
            'application/x-zip-compressed' => self::Zip,
            'application/xml' => self::Xml,
            'application/zip' => self::Zip,
            'audio/flac' => self::Flac,
            'audio/m4a' => self::M4a,
            'audio/mp3' => self::Mp3,
            'audio/mp4' => self::M4a,
            'audio/mpeg' => self::Mp3,
            'audio/ogg' => self::Ogg,
            'audio/wav' => self::Wav,
            'audio/x-wav' => self::Wav,
            'image/apng' => self::Apng,
            'image/avif' => self::Avif,
            'image/gif' => self::Gif,
            'image/jpeg' => self::Jpg,
            'image/pjpeg' => self::Pjpeg,
            'image/png' => self::Png,
            'image/svg+xml' => self::Svg,
            'image/webp' => self::Webp,
            'text/csv' => self::Csv,
            'text/plain' => self::Txt,
            'text/xml' => self::Xml,
            'video/avi' => self::Avi,
            'video/mp4' => self::Mp4,
            'video/quicktime' => self::Mov,
            'video/webm' => self::Webm,
            'video/x-matroska' => self::Mkv,
            'video/x-msvideo' => self::Avi,
        ][$mimeType] ?? throw new ValueError("$mimeType is not a supported file MIME type");
    }

    public function folder(): string
    {
        return match (true) {
            self::containsExtension(self::archiveExtensions(), $this) => self::FOLDER_ARCHIVE,
            self::containsExtension(self::audioExtensions(), $this) => self::FOLDER_AUDIO,
            self::containsExtension(self::documentExtensions(), $this) => self::FOLDER_DOCUMENT,
            self::containsExtension(self::imageExtensions(), $this) => self::FOLDER_IMAGE,
            self::containsExtension(self::videoExtensions(), $this) => self::FOLDER_VIDEO,
            default => self::FOLDER_FILE,
        };
    }

    private static function containsExtension(array $extensions, self $fileExtension): bool
    {
        foreach ($extensions as $extension) {
            if (cmprenum($extension, $fileExtension)) {
                return true;
            }
        }

        return false;
    }
}
