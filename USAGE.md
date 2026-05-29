## Usage

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

## Save One File

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

## Save Three Files

```php
use Mantax559\LaravelFiles\Services\FileManager;
use Mantax559\LaravelFiles\Services\FileTransaction;

$files = FileTransaction::run(fn (FileTransaction $transaction): array => (new FileManager)->create([
    $request->file('files.0')->path(),
    $request->file('files.1')->path(),
    $request->file('files.2')->path(),
], 'pages', $transaction));
```

## Create One Page With One File

```php
use App\Models\Page;
use Mantax559\LaravelFiles\Services\FileManager;
use Mantax559\LaravelFiles\Services\FileTransaction;

$page = FileTransaction::run(function (FileTransaction $transaction) use ($request): Page {
    $file = (new FileManager)->create(
        $request->file('file')->path(),
        'pages',
        $transaction
    );

    return Page::query()->create([
        'title' => $request->input('title'),
        'file_id' => $file->getKey(),
    ]);
});
```

## Create Three Pages With Three Files

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
