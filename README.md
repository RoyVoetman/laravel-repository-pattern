# Laravel Extras

## Introduction
This Laravel package provide developers with useful class preset, artisan commands, and helpers that are not included in Laravel by default.

[![Latest Version](https://img.shields.io/packagist/v/royvoetman/laravel-extras.svg?style=flat-square)](https://packagist.org/packages/royvoetman/laravel-extras)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/royvoetman/laravel-extras.svg?style=flat-square)](https://packagist.org/packages/royvoetman/laravel-extras)

### Included features
* Repository pattern 
* Auto discover Form Requests attributes
* Preparing Request Data before it is passed to the Validator
* View and Route prefixes in controllers

## Installation

```bash
composer require royvoetman/laravel-extras
```

If you are on Laravel 5.4 or earlier, then register the service provider in app.php

```php
'providers' => [
    // ...
    RoyVoetman\Extras\ExtrasServiceProvider::class,
]
```

If you are on Laravel 5.5 or higher, composer will have registered the provider automatically for you.

## Docs

### Repository pattern 

### Auto discover Form Requests attributes

### Preparing Request Data before it is passed to the Validator

### View and Route prefixes in controllers


## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.