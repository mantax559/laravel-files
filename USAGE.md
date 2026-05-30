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
        'xlsx,
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

$file = FileTransaction::run(fn (FileTransaction $transaction): File => (new FileManager)->create(
    $request->file('file')->path(),
    'pages',
    $transaction
));
```

## Save Multiple Files

```php
use Mantax559\LaravelFiles\Services\FileManager;
use Mantax559\LaravelFiles\Services\FileTransaction;

$files = FileTransaction::run(fn (FileTransaction $transaction): array => (new FileManager)->create([
    $request->file('files.0')->path(),
    $request->file('files.1')->path(),
    $request->file('files.2')->path(),
], 'pages', $transaction));
```

## Create Multiple Models With Files

```php
use App\Models\Page;
use Mantax559\LaravelFiles\Services\FileManager;
use Mantax559\LaravelFiles\Services\FileTransaction;

$pages = FileTransaction::run(function (FileTransaction $transaction) use ($request): array {
    $fileManager = new FileManager;
    $pages = [];

    foreach ($request->file('files') as $index => $uploadedFile) {
        $file = $fileManager->create(
            $uploadedFile->path(),
            'pages',
            $transaction
        );

        $pages[] = Page::query()->create([
            'title' => $request->input("pages.$index.title"),
            'file_id' => $file->getKey(),
        ]);
    }

    return $pages;
});
```

## Delete Files

```php
use Mantax559\LaravelFiles\Services\FileManager;
use Mantax559\LaravelFiles\Services\FileTransaction;

FileTransaction::run(function (FileTransaction $transaction) use ($page): void {
    (new FileManager)->destroy($page->file, $transaction);

    $page->delete();
});
```

## Open And Download

```php
use Mantax559\LaravelFiles\Services\FileManager;

return FileManager::open($file->path, 'application/pdf');
return FileManager::download($file->path);
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
cache_image($page->file->path, 'page', $page);
email_image($page->file->path, 'page', $message, $page);
email_image('image/logo.png', 'logo', $message);
```

When a cached image cannot be created, the package logs the failure and returns `config('laravel-files.default_image_cache_url')`.

## Stored Paths

Uploaded files are stored under folders resolved by `FileExtension`:

```php
document/invoices/{uuid}.pdf
image/products/{uuid}.avif
video/pages/{uuid}.mp4
```

Images are resized before storage. Convertible image formats are stored as `FileExtension::STORED_IMAGE_EXTENSION`; other image formats keep their original extension.