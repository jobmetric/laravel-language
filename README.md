[contributors-shield]: https://img.shields.io/github/contributors/jobmetric/laravel-language.svg?style=for-the-badge
[contributors-url]: https://github.com/jobmetric/laravel-language/graphs/contributors
[forks-shield]: https://img.shields.io/github/forks/jobmetric/laravel-language.svg?style=for-the-badge&label=Fork
[forks-url]: https://github.com/jobmetric/laravel-language/network/members
[stars-shield]: https://img.shields.io/github/stars/jobmetric/laravel-language.svg?style=for-the-badge
[stars-url]: https://github.com/jobmetric/laravel-language/stargazers
[license-shield]: https://img.shields.io/github/license/jobmetric/laravel-language.svg?style=for-the-badge
[license-url]: https://github.com/jobmetric/laravel-language/blob/master/LICENCE.md
[linkedin-shield]: https://img.shields.io/badge/-LinkedIn-blue.svg?style=for-the-badge&logo=linkedin&colorB=555
[linkedin-url]: https://linkedin.com/in/majidmohammadian

[![Contributors][contributors-shield]][contributors-url]
[![Forks][forks-shield]][forks-url]
[![Stargazers][stars-shield]][stars-url]
[![MIT License][license-shield]][license-url]
[![LinkedIn][linkedin-shield]][linkedin-url]

# Language for laravel

It is a standard package for managing different system languages in Laravel.

## Install via composer

Run the following command to pull in the latest version:
```bash
composer require jobmetric/laravel-language
```

## Documentation

This package evolves every day under continuous development and integrates a diverse set of features. It's a must-have asset for Laravel enthusiasts and provides a seamless way to align your projects with basic language models.

To use the services of this package, please follow the instructions below.

>#### Before doing anything, you must migrate after installing the package by composer.

```bash
php artisan migrate
```

### Usage

#### Store language

You can store a new language by using the following code:

```php
use JobMetric\Language\Facades\Language

$language = Language::store([
    'name' => 'English',
    'flag' => 'us',
    'locale' => 'en',
    'direction' => 'ltr',
    'status' => true
]);
```

#### Update language

You can update an existing language by using the following code:

```php
use JobMetric\Language\Facades\Language

$language = Language::update($language->id, [
    'name' => 'English',
    'flag' => 'us',
    'locale' => 'en',
    'direction' => 'ltr',
    'status' => true
]);
```

#### Delete language

You can delete an existing language by using the following code:

```php
use JobMetric\Language\Facades\Language

$language = Language::delete($language->id);
```

#### Get all languages

You can get all languages by using the following code:

```php
use JobMetric\Language\Facades\Language

$languages = Language::all();
```

#### Get language by id

You can get a language by id by using the following code:

```php
use JobMetric\Language\Facades\Language

$language = Language::all([
    'id' => $language->id
]);
```

#### Get language by locale

You can get a language by locale by using the following code:

```php
use JobMetric\Language\Facades\Language

$language = Language::all([
    'locale' => 'en'
]);
```

## Rules

This package contains several rules for which you can write a listener as follows

- `CheckLocaleRule` - This rule checks if the locale exists in the language table.

```php
use JobMetric\Language\Rules\CheckLocaleRule;

$request->validate([
    'locale' => ['required', new CheckLocaleRule]
]);
```

- `LanguageExistsRule` - This rule checks if the language exists in the language table.

## Events

This package contains several events for which you can write a listener as follows

| Event                 | Description                                         |
|-----------------------|-----------------------------------------------------|
| `LanguageStoredEvent` | This event is called after storing the language.    |
| `LanguageUpdateEvent` | This event is called after updating the language.   |
| `LanguageForgetEvent` | This event is called after forgetting the language. |


## Contributing

Thank you for considering contributing to the Laravel Language! The contribution guide can be found in the [CONTRIBUTING.md](https://github.com/jobmetric/laravel-language/blob/master/CONTRIBUTING.md).

## License

The MIT License (MIT). Please see [License File](https://github.com/jobmetric/laravel-language/blob/master/LICENCE.md) for more information.
