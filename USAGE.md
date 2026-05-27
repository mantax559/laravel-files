## Usage

## Configuration

Main config keys:

```php
use Mantax559\LaravelFiles\Enums\FileExtension;

'table' => 'files',
'disk' => 'local',
'image_cache_disk' => 'public',
'image_cache_quality' => 90,
'max_file_size_bytes' => 24 * 1024 * 1024,
'max_upload_file_size_bytes' => 48 * 1024 * 1024,
'max_image_side_pixels' => 2048,
'max_upload_image_side_pixels' => 8192,
'accept_document_extensions' => [
    FileExtension::Pdf,
    FileExtension::Xlsx,
],
```

Accepted extensions are configured by file category:

- `accept_archive_extensions`
- `accept_audio_extensions`
- `accept_document_extensions`
- `accept_image_extensions`
- `accept_video_extensions`
- `accept_file_extensions`

Each value should be a `FileExtension` enum case. If an extension is not present in the matching category list, uploads using that extension are rejected.

Images are stored as `avif` by default when the source extension can be converted. The stored file path and the `extension` model value should use the final stored extension.

## Validation

```php
use Mantax559\LaravelFiles\Helpers\FileValidationHelper;

FileValidationHelper::getFileRules();
FileValidationHelper::getArchiveRules();
FileValidationHelper::getAudioRules();
FileValidationHelper::getDocumentRules();
FileValidationHelper::getImageRules();
FileValidationHelper::getVideoRules();
```

Rules use the configured accepted extensions and upload size limits. Custom extensions can be passed per call:

```php
use Mantax559\LaravelFiles\Enums\FileExtension;
use Mantax559\LaravelFiles\Helpers\FileValidationHelper;

FileValidationHelper::getDocumentRules(mimes: [
    FileExtension::Pdf,
    FileExtension::Xlsx,
]);

FileValidationHelper::getImageRules(width: 1024, height: 1024);
FileValidationHelper::getImageRules(required: false, maxWidth: 2048, maxHeight: 2048);
```

`getFileRules()` allows all configured categories. Category-specific methods only allow extensions from that category.

## Store Files

```php
use Mantax559\LaravelFiles\Services\FileService;

$path = (new FileService)->save($request->file('file')->path(), 'products');
```

The returned `$path` is relative to `config('laravel-files.disk')`.

Folder structure is derived from the detected `FileExtension`:

- `archive/{folder}/{uuid}.{extension}`
- `audio/{folder}/{uuid}.{extension}`
- `document/{folder}/{uuid}.{extension}`
- `image/{folder}/{uuid}.avif`
- `video/{folder}/{uuid}.{extension}`
- `file/{folder}/{uuid}.{extension}`

When the source is configured as `FileSource::Seeder`, paths are prefixed with `seeder`:

```php
use Mantax559\LaravelFiles\Enums\FileSource;
use Mantax559\LaravelFiles\Services\FileService;

$path = (new FileService(FileSource::Seeder))->save($filePath, 'products');
```

Seeder storage removes the target folder before the first write for that folder during the service instance lifetime.

## Cache Images

```php
use Mantax559\LaravelFiles\Services\FileService;

$url = (new FileService)->cacheImage(
    sourcePath: 'image/products/source.avif',
    width: 300,
    height: 300,
    folder: 'products',
);
```

Cached images are stored on `config('laravel-files.image_cache_disk')` under `cache/image/{folder}` and the method returns the public disk URL.

Multiple cache sizes:

```php
use Mantax559\LaravelFiles\Services\FileService;

$urls = (new FileService)->cacheImages('image/products/source.avif', [
    ['width' => 300, 'height' => 300],
    ['width' => 600, 'height' => 600],
], 'products');
```

## Rollback Files

Use `transactionWithFileRollback()` when database writes and file writes must succeed together:

```php
use Mantax559\LaravelFiles\Services\FileService;

$fileService = new FileService;

FileService::transactionWithFileRollback(function () use ($fileService, $request): void {
    $path = $fileService->save($request->file('file')->path(), 'products');

    Product::query()->create([
        'file_path' => $path,
    ]);
}, $fileService);
```

If the callback throws, uploaded files are deleted and deleted files tracked by the service are restored.

## File Model

```php
use Mantax559\LaravelFiles\Enums\FileExtension;
use Mantax559\LaravelFiles\Enums\FileSource;
use Mantax559\LaravelFiles\Models\File;

File::query()->create([
    'path' => $path,
    'extension' => FileExtension::Avif,
    'source' => FileSource::Manual,
    'size' => 123456,
]);
```

`extension` and `source` are cast to enums. The table name is read from `config('laravel-files.table')`.

## Enums

```php
use Mantax559\LaravelFiles\Enums\FileExtension;
use Mantax559\LaravelFiles\Enums\FileSource;

FileExtension::Jpg->folder();
FileExtension::getByMimeType('image/jpeg');
FileExtension::getArrayForSelect();

FileSource::Manual;
FileSource::Seeder;
```

`FileExtension::folder()` returns the storage category used by `FileService`.
