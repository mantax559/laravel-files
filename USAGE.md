## Usage

## Validation

Use the helper methods in form requests or controllers:

```php
use Mantax559\LaravelFiles\Enums\FileExtension;
use Mantax559\LaravelFiles\Helpers\FileValidationHelper;

return [
    'file' => FileValidationHelper::getFileRules(),
    'document' => FileValidationHelper::getDocumentRules(mimes: [
        FileExtension::Pdf,
        'xlsx',
    ]),
    'image' => FileValidationHelper::getImageRules(maxWidth: 2048, maxHeight: 2048),
];
```

Available validation helpers:

```php
use Mantax559\LaravelFiles\Helpers\FileValidationHelper;

FileValidationHelper::getFileRules();
FileValidationHelper::getArchiveRules();
FileValidationHelper::getAudioRules();
FileValidationHelper::getDocumentRules();
FileValidationHelper::getImageRules();
FileValidationHelper::getVideoRules();
```

## Save One File

Always save files inside `FileTransaction::run(...)` so uploaded files are rolled back when your database work fails.

```php
use Mantax559\LaravelFiles\Models\File;
use Mantax559\LaravelFiles\Services\FileManager;
use Mantax559\LaravelFiles\Services\FileTransaction;

$file = FileTransaction::run(function (FileTransaction $transaction) use ($request): File {
    $fileManager = new FileManager;

    return $fileManager->create(
        $request->file('file')->path(),
        'pages',
        $transaction
    );
});
```

## Save Multiple Files

```php
use Mantax559\LaravelFiles\Services\FileManager;
use Mantax559\LaravelFiles\Services\FileTransaction;

$files = FileTransaction::run(function (FileTransaction $transaction) use ($request): array {
    $fileManager = new FileManager;

    return $fileManager->create([
        $request->file('files.0')->path(),
        $request->file('files.1')->path(),
        $request->file('files.2')->path(),
    ], 'pages', $transaction);
});
```

## Create One Model With Multiple Files

```php
use App\Models\Page;
use Mantax559\LaravelFiles\Services\FileManager;
use Mantax559\LaravelFiles\Services\FileTransaction;

$page = FileTransaction::run(function (FileTransaction $transaction) use ($request): Page {
    $fileManager = new FileManager;

    $files = $fileManager->create([
        $request->file('desktop_image')->path(),
        $request->file('mobile_image')->path(),
        $request->file('document')->path(),
    ], 'pages', $transaction);

    return Page::query()->create([
        'title' => $request->input('title'),
        'desktop_image_id' => $files[0]->getKey(),
        'mobile_image_id' => $files[1]->getKey(),
        'document_id' => $files[2]->getKey(),
    ]);
});
```

## Delete Files

```php
use Mantax559\LaravelFiles\Services\FileManager;
use Mantax559\LaravelFiles\Services\FileTransaction;

FileTransaction::run(function (FileTransaction $transaction) use ($page): void {
    (new FileManager)->destroy([
        $page->desktopImage,
        $page->mobileImage,
        $page->document,
    ], $transaction);

    $page->delete();
});
```

## Open And Download

```php
use Mantax559\LaravelFiles\Services\FileManager;

return FileManager::open($file);
return FileManager::download($file);
```

## Cached Images

Configure named sizes in `config/laravel-files.php`:

```php
'image_cache_sizes' => [
    'thumbnail' => ['width' => 300, 'height' => 300],
    'banner' => ['width' => 1200],
],
```

Use the global helpers:

```php
cache_image($page->file, 'page');
email_image($page->file, 'page', $message);
email_image('image/logo.png', 'logo', $message);
```

When a cached image cannot be created, the package logs the failure and returns `config('laravel-files.default_image_cache_url')`.

## Stored Paths


```php
document/invoices/{id}.pdf
image/pages/{page_id}/{id}.avif
```

Cache copies the same folder structure and adds `cache/` plus a size file inside the `{id}` folder:

```php
cache/{folder}/{id}/{width}x{height}.avif
```

Example:

```php
cache/image/pages/{page_id}/{id}/300x300.avif
```

Images are resized before storage. Convertible formats are saved as `FileExtension::STORED_IMAGE_EXTENSION`; other formats keep their original extension.