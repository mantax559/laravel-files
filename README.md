![GitHub release (latest by date)](https://img.shields.io/github/v/release/mantax559/laravel-files?label=latest&style=flat-square)
![GitHub release (latest SemVer including pre-releases)](https://img.shields.io/github/v/release/mantax559/laravel-files?include_prereleases&label=pre-release&style=flat-square)
![Packagist](https://img.shields.io/packagist/l/mantax559/laravel-files?style=flat-square)
![PHP from Packagist](https://img.shields.io/packagist/php-v/mantax559/laravel-files?style=flat-square)

# Laravel Files

## Requirements

- **FFI** - required for libvips image processing
- **Xdebug** - required for code coverage analysis (recommended for development)

## Installation & Setup

You can install the package via composer:

    composer require mantax559/laravel-files

After installing the package, run the migration command to create the necessary database tables:

    php artisan migrate

The package will automatically register its service provider.

## Usage

See **[USAGE.md](USAGE.md)** for more information about the package and examples.

## Customisation

### Config

You can optionally publish the config file with:

    php artisan vendor:publish --provider="Mantax559\LaravelFiles\Providers\AppServiceProvider" --tag=config

Publish assets:

    php artisan vendor:publish --provider="Mantax559\LaravelFiles\Providers\AppServiceProvider" --tag=laravel-assets

### Testing

Run the test suite:

    composer test

Coverage summary in the terminal:

    composer test-coverage

For a browsable HTML report, generate files under `coverage/` and open `coverage/index.html` in your browser:

    composer test-coverage-html

## Credits

- [All Contributors](../../contributors)

## License

This project is proprietary and confidential software. You are not permitted to copy, modify, distribute, sublicense, publish, sell, share, or use any part of this project without explicit written permission from the owner. Unauthorized access, usage, reproduction, or redistribution of this software, in whole or in part, is strictly prohibited.