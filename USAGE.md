## Usage

After package installation, add this Artisan command to `composer.json`:

```json
"post-update-cmd": [
    "@php artisan settings:synchronize"
]
```

```bash
php artisan settings:synchronize
```

This command makes the database match your configured settings list: it inserts missing keys, updates the `type` and `is_private` metadata when needed (without changing existing DB `value`), removes keys that are no longer present in config, and refreshes cache for all synchronized keys.

## Configuration

Main config keys:

```php
use Mantax559\LaravelFiles\Models\Setting;
use Mantax559\LaravelFiles\Enums\SettingType;

'model' => Setting::class,
'table' => 'settings',
'cache_key_prefix' => 'settings',
'encryption' => true,
'settings' => [
    [
        'key' => 'default_pagination_per_page',
        'value' => 10,
        'type' => SettingType::Integer,
        'is_private' => false,
    ],
],
```

Every synchronized setting must have `key`, `value`, `type`, and `is_private`. In config, `type` must be a `SettingType` enum case. If `settings` is empty, synchronization deletes existing settings.

## Read values

```php
use Mantax559\LaravelFiles\Models\Setting;

setting('default_pagination_per_page');
setting('missing_key', '10');
setting('missing_key', '10', 'app.fallback_locale');
setting('default_pagination_per_page', cache: false);
Setting::get('default_pagination_per_page', cache: false); // same bypass behavior as setting(...)
Setting::get('missing_key', '10'); // returns provided default value when key is missing
Setting::get('missing_key', '10', 'app.fallback_locale'); // returns config('app.fallback_locale') when it exists
Setting::isEmpty('some_key'); // bool
```

When a key is missing and no default or fallback config value is available, `Setting::get(...)` throws `InvalidArgumentException`. If the settings table does not exist yet, reads return `null`.

## Write values

```php
use Mantax559\LaravelFiles\Enums\SettingType;
use Mantax559\LaravelFiles\Models\Setting;

Setting::set('title', 'My Project', SettingType::String); // "My Project"
Setting::set('title', 'My Project', 'string'); // same as SettingType::String
Setting::set('api_key', 'secret', SettingType::String, true); // "secret"
Setting::set('count', 10, SettingType::Integer); // int(10)
Setting::set('rate', 10.5, SettingType::Float); // float(10.5)
Setting::set('enabled', true, SettingType::Boolean); // bool(true)
Setting::set('enabled', 'true', SettingType::Boolean); // bool(true)
Setting::set('enabled', '1', SettingType::Boolean); // bool(true)
Setting::set('enabled', 'ok', SettingType::Boolean); // bool(false)
Setting::set('enabled', 'false', SettingType::Boolean); // bool(false)
Setting::set('enabled', '0', SettingType::Boolean); // bool(false)
Setting::set('data', ['a' => 1], SettingType::Json); // ['a' => 1]
Setting::set('scalar_json', 42, SettingType::Json); // int(42)
Setting::set('nullable_json', null, SettingType::Json); // null
```

`Setting::set(...)` accepts `SettingType` enum or string type name (`string`, `integer`, `float`, `boolean`, `json`) for the third parameter. The fourth parameter is `is_private` (default `false`): when `true`, the stored value is treated as sensitive-your application should hide or mask it and avoid showing the full value to end users (for example API keys and tokens).

Supported input value types:

- `string`
- `int`
- `float`
- `bool`
- `array`
- `null`

`Setting::set(...)` returns the typed value after saving. It throws `InvalidArgumentException` if the settings table is not ready.

## Key normalization

`Setting::set(...)` normalizes keys:

```php
Setting::set('My Key', 'x', SettingType::String);
Setting::get('my_key'); // "x"
```

## Custom setting model

If your application needs traits on the package model, create your own model that extends the package model:

```php
namespace App\Models;

use Mantax559\LaravelFiles\Models\Setting as BaseSetting;

class Setting extends BaseSetting
{
    use SomeProjectTrait;
}
```

Then point the package config to that model:

```php
'model' => App\Models\Setting::class,
```

Useful public helpers for advanced cases:

```php
Setting::formatKey('My Key'); // "my_key"
Setting::tableExists(); // bool
Setting::modelClass(); // configured model class
SettingType::getArray();
SettingType::getArrayForSelect();
SettingType::getEnumByString('string');
```