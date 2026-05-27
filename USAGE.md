## Usage

### File limits

Configure file upload validation and storage limits through `config/laravel-files.php`. `FileValidationHelper::getFileRules()` and `FileValidationHelper::getImageRules()` use the upload limits for request validation, and `FileService` uses the same package settings when storing and processing files.

### Validation

```php
use Mantax559\LaravelFiles\Helpers\FileValidationHelper;

FileValidationHelper::getFileRules();
FileValidationHelper::getFileRules(mimes: ['pdf', 'xlsx']);
FileValidationHelper::getImageRules();
FileValidationHelper::getImageRules(width: 1024, height: 1024);
```
