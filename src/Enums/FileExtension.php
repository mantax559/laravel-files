<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Enums;

use Mantax559\LaravelHelpers\Traits\EnumTrait;

enum FileExtension: string
{
    use EnumTrait;

    case SevenZip = '7z';
    case Apng = 'apng';
    case Avif = 'avif';
    case Avi = 'avi';
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
    case Pjp = 'pjp';
    case Pjpeg = 'pjpeg';
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
            'application/vnd.rar' => self::Rar,
            'application/vnd.oasis.opendocument.spreadsheet' => self::Ods,
            'application/vnd.oasis.opendocument.text' => self::Odt,
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => self::Pptx,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => self::Xlsx,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => self::Docx,
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
            'video/x-msvideo' => self::Avi,
            'video/x-matroska' => self::Mkv,
        ][$mimeType] ?? throw new \ValueError("$mimeType is not a supported file MIME type");
    }
}
