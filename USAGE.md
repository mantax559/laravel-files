## Usage

### File limits

Configure file upload validation and storage limits through the package environment values. `FileValidationHelper::getFileRules()` and `FileValidationHelper::getImageRules()` use the upload limits for request validation, and `FileService` uses the same package settings when storing and processing files.

```env
FILES_MAX_UPLOAD_FILE_SIZE_BYTES=52428800
FILES_MAX_FILE_SIZE_BYTES=26214400
FILES_MAX_UPLOAD_IMAGE_SIDE_PIXELS=8192
FILES_MAX_IMAGE_SIDE_PIXELS=2048
```

`FILES_MAX_UPLOAD_FILE_SIZE_BYTES` is the maximum original upload size before image processing. `FILES_MAX_FILE_SIZE_BYTES` is the maximum stored file size after image resizing and WebP conversion. `FILES_MAX_UPLOAD_IMAGE_SIDE_PIXELS` protects validation and decoding from extremely large source images, while `FILES_MAX_IMAGE_SIDE_PIXELS` is the final stored image side limit.

### Validation

```php
use Mantax559\LaravelFiles\Helpers\FileValidationHelper;

FileValidationHelper::getFileRules();
FileValidationHelper::getFileRules(mimes: 'pdf,xlsx');
FileValidationHelper::getImageRules();
FileValidationHelper::getImageRules(width: 1024, height: 1024);
```
