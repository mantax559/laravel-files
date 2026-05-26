<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use JsonException;
use Mantax559\LaravelFiles\Enums\SettingType;

class Setting extends Model
{
    use HasUuids;

    protected $fillable = [
        'key',
        'value',
        'type',
        'is_private',
    ];

    protected $casts = [
        'type' => SettingType::class,
        'is_private' => 'boolean',
    ];

    public $timestamps = true;

    protected $guarded = ['id'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('laravel-settings.table'));
    }

    protected static function booted(): void
    {
        static::updated(function (Setting $setting): void {
            static::forgetCache($setting->key);
            static::get($setting->key, cache: false);
        });

        static::deleted(function (Setting $setting): void {
            static::forgetCache($setting->key);
        });
    }

    public static function get(string $key, mixed $defaultValue = null, ?string $fallbackConfigKey = null, bool $cache = true): mixed
    {
        if (static::shouldProxyToConfiguredModel()) {
            return static::modelClass()::get(...func_get_args());
        }

        $key = static::formatKey($key);
        $hasDefaultValue = array_key_exists(1, func_get_args());
        $cacheValue = Cache::get(static::formatCacheKey($key));

        if (static::isValueEmpty($cacheValue) || ! $cache) {
            $setting = static::retrieveSettingByKey($key);

            if (empty($setting)) {
                if (! static::tableExists()) {
                    return null;
                }

                if (! static::isValueEmpty($fallbackConfigKey) && config()->has($fallbackConfigKey)) {
                    return config($fallbackConfigKey);
                }

                if ($hasDefaultValue) {
                    return $defaultValue;
                }

                throw new InvalidArgumentException(__('Setting key :key does not exist.', ['key' => $key]));
            }

            $cacheValue = static::rememberCacheValue($key, $setting);
        }

        if (is_null($cacheValue['value'])) {
            return null;
        }

        return match ($cacheValue['type']) {
            SettingType::Json => static::decodeJson($cacheValue['value']),
            SettingType::String => (string) $cacheValue['value'],
            SettingType::Float => (float) $cacheValue['value'],
            SettingType::Integer => (int) $cacheValue['value'],
            SettingType::Boolean => filter_var($cacheValue['value'], FILTER_VALIDATE_BOOLEAN),
        };
    }

    public static function set(string $key, string|int|float|bool|array|null $value, string|SettingType $settingType, bool $isPrivate = false): mixed
    {
        if (static::shouldProxyToConfiguredModel()) {
            return static::modelClass()::set(...func_get_args());
        }

        if (is_string($settingType)) {
            $settingType = SettingType::getEnumByString($settingType);
        }

        $key = static::formatKey($key);
        $value = static::formatValue($value, $settingType);

        try {
            static::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'type' => $settingType, 'is_private' => $isPrivate],
            );
        } catch (QueryException $e) {
            if (! static::tableExists()) {
                throw new InvalidArgumentException('Settings table is not ready. Run migrations first.', 0, $e);
            }

            throw $e;
        }

        return static::get($key, cache: false);
    }

    public static function isEmpty(string $key): bool
    {
        return static::isValueEmpty(static::get($key));
    }

    private static function forgetCache(string $key): void
    {
        Cache::forget(static::formatCacheKey($key));
    }

    public static function formatKey(string $key): string
    {
        $formatted = format_string($key, 3);

        if (static::isValueEmpty($formatted)) {
            throw new InvalidArgumentException('Setting key cannot be empty.');
        }

        return str_replace(' ', '_', $formatted);
    }

    public static function tableExists(): bool
    {
        if (static::shouldProxyToConfiguredModel()) {
            return static::modelClass()::tableExists();
        }

        try {
            return Schema::hasTable((new static)->getTable());
        } catch (QueryException) {
            return false;
        }
    }

    public static function modelClass(): string
    {
        if (! is_string(config('laravel-settings.model')) || ! is_a(config('laravel-settings.model'), self::class, true)) {
            throw new InvalidArgumentException('Invalid settings model class.');
        }

        return config('laravel-settings.model');
    }

    protected static function retrieveSettingByKey(string $key): ?self
    {
        if (! static::tableExists()) {
            return null;
        }

        $setting = static::where('key', $key)->first();

        return $setting instanceof self
            ? $setting
            : null;
    }

    protected static function formatValue(string|int|float|bool|array|null $value, SettingType $settingType): ?string
    {
        if (cmprenum($settingType, SettingType::Json)) {
            try {
                $encoded = match (true) {
                    is_array($value) => json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                    is_null($value) => json_encode(null, JSON_THROW_ON_ERROR),
                    is_string($value) => $value,
                    default => json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                };
            } catch (JsonException $e) {
                throw new InvalidArgumentException('Cannot encode value as JSON: '.$e->getMessage(), 0, $e);
            }

            static::decodeJson($encoded);

            return $encoded;
        }

        if (is_array($value)) {
            throw new InvalidArgumentException('Array value can only be used with JSON settings.');
        }

        $scalar = static::scalarToFormattedString($value, $settingType);

        if (static::isValueEmpty($scalar)) {
            return null;
        }

        return $scalar;
    }

    protected static function scalarToFormattedString(string|int|float|bool|null $value, SettingType $settingType): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (is_bool($value)) {
            if (cmprenum($settingType, SettingType::String) || cmprenum($settingType, SettingType::Boolean)) {
                return $value ? 'true' : 'false';
            }

            return $value ? '1' : '0';
        }

        if (cmprenum($settingType, SettingType::String)) {
            return static::stringToFormattedString($value);
        }

        if (cmprenum($settingType, SettingType::Integer)) {
            return static::integerToFormattedString($value);
        }

        if (cmprenum($settingType, SettingType::Float)) {
            return static::floatToFormattedString($value);
        }

        return static::booleanToFormattedString($value);
    }

    protected static function stringToFormattedString(string|int|float $value): ?string
    {
        $formattedString = format_string($value);

        return static::isValueEmpty($formattedString) && cmprstr($value, '0')
            ? '0'
            : $formattedString;
    }

    protected static function integerToFormattedString(string|int|float $value): string
    {
        $integer = filter_var($value, FILTER_VALIDATE_INT);

        if (! is_int($integer)) {
            throw new InvalidArgumentException('Integer setting value must be a valid integer.');
        }

        return (string) $integer;
    }

    protected static function floatToFormattedString(string|int|float $value): string
    {
        if (! is_numeric($value)) {
            throw new InvalidArgumentException('Float setting value must be numeric.');
        }

        return (string) (float) $value;
    }

    protected static function booleanToFormattedString(string|int|float $value): string
    {
        $boolean = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if (is_null($boolean)) {
            throw new InvalidArgumentException('Boolean setting value must be a valid boolean.');
        }

        return $boolean ? 'true' : 'false';
    }

    protected static function isValueEmpty(mixed $value): bool
    {
        if (is_bool($value)) {
            return false;
        }

        if (is_array($value)) {
            return empty($value);
        }

        return empty($value) && ! cmprstr($value, '0');
    }

    protected static function decodeJson(string $value): mixed
    {
        try {
            return json_decode($value, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException('The provided value is not valid JSON: '.$e->getMessage(), 0, $e);
        }
    }

    protected static function formatCacheKey(string $key): string
    {
        $fullKey = implode('.', [config('laravel-settings.cache_key_prefix'), $key]);

        return config('laravel-settings.encryption')
            ? md5($fullKey)
            : $fullKey;
    }

    protected static function rememberCacheValue(string $key, self $setting): array
    {
        $cacheValue = [
            'value' => $setting->value,
            'type' => $setting->type,
        ];

        Cache::forever(static::formatCacheKey($key), $cacheValue);

        return $cacheValue;
    }

    protected static function shouldProxyToConfiguredModel(): bool
    {
        return cmprstr(static::class, self::class) && ! cmprstr(static::modelClass(), self::class);
    }
}
