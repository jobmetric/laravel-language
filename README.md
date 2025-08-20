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

# Laravel Language

A clean, framework-native way to manage application languages with first-class validation rules, events, and a fluent query API.

## Table of Contents
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration & Migrations](#configuration--migrations)
- [Data Model](#data-model)
- [Calendars Enum](#calendars-enum)
- [Validation Rules](#validation-rules)
- [Requests (FormRequest)](#requests-formrequest)
- [Service / Facade API](#service--facade-api)
- [Querying & Filters](#querying--filters)
- [Events](#events)
- [API Resources (Optional)](#api-resources-optional)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)

---

## Features
- **Language entity** with:  
  `name`, `flag`, `locale` (two-letter like `fa`, `en`), `direction` (`ltr`/`rtl`), `calendar`, `first_day_of_week` (0..6), `status`.
- **Calendar awareness** via enum (Gregorian, Jalali, Hijri, Hebrew, Buddhist, Coptic, Ethiopian, Chinese).
- **Validation rules**: `CheckLocaleRule`, `LanguageExistRule`.
- **Service/Facade** for CRUD and fluent querying (Spatie QueryBuilder under the hood).
- **Domain events** on store/update/delete.
- **API-ready** via `LanguageResource` (optional).

> Note: The package no longer uses formatting fields like `time_format`, `date_format_short`, or `date_format_long`.

---

## Requirements
- PHP **8.2+**
- Laravel **10/11/12**
- A supported database (MySQL/MariaDB, etc.)

---

## Installation
```bash
composer require jobmetric/laravel-language
```

Run migrations:
```bash
php artisan migrate
```

---

## Configuration & Migrations
- The package ships with migrations for the `languages` table.
- (Optional) If a config file is provided, you may publish it:
```bash
php artisan vendor:publish --tag=language-config
```

Seeders/Factories (optional):
- You can seed an initial default language (e.g., Persian `fa`) in your application’s seeders/factories.

---

## Data Model
**Table:** `languages`

Recommended columns:
- `id` *(int, PK)*
- `name` *(string)*
- `flag` *(string|null)* — e.g., `ir`, `us`
- `locale` *(string)* — **two letters** like `fa`, `en` (not `fa-IR`)
- `direction` *(enum)* — `ltr` or `rtl`
- `calendar` *(enum)* — see [Calendars Enum](#calendars-enum)
- `first_day_of_week` *(tinyint 0..6)* — `0=Saturday, 1=Sunday, ..., 6=Friday`
- `status` *(bool)*
- Timestamps

---

## Calendars Enum
```php
enum CalendarTypeEnum: string {
    case GREGORIAN = 'gregorian';
    case JALALI    = 'jalali';
    case HIJRI     = 'hijri';
    case HEBREW    = 'hebrew';
    case BUDDHIST  = 'buddhist';
    case COPTIC    = 'coptic';
    case ETHIOPIAN = 'ethiopian';
    case CHINESE   = 'chinese';
}
```

---

## Validation Rules

### `CheckLocaleRule`
Validates that a provided `locale` conforms to your system’s accepted locales.
```php
use JobMetric\Language\Rules\CheckLocaleRule;

$request->validate([
    'locale' => ['required', 'string', new CheckLocaleRule],
]);
```

### `LanguageExistRule`
Validates that a language record exists (commonly used for `language_id`).
```php
use JobMetric\Language\Rules\LanguageExistRule;

$request->validate([
    'language_id' => ['required', new LanguageExistRule('fa')], // optionally scope by locale
]);
```

---

## Requests (FormRequest)
Example for creating a language:
```php
use Illuminate\Foundation\Http\FormRequest;
use JobMetric\Language\Enums\CalendarTypeEnum;
use JobMetric\Language\Rules\CheckLocaleRule;

class StoreLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name'              => 'required|string',
            'flag'              => 'nullable|string',
            'locale'            => ['required','string', new CheckLocaleRule],
            'direction'         => 'required|string|in:ltr,rtl',
            'calendar'          => 'required|string|in:' . implode(',', CalendarTypeEnum::values()),
            'first_day_of_week' => 'required|integer|min:0|max:6',
            'status'            => 'required|boolean',
        ];
    }
}
```

---

## Service / Facade API
Facade: `JobMetric\Language\Facades\Language`

### Store
```php
$language = Language::store([
    'name'              => 'Persian',
    'flag'              => 'ir',
    'locale'            => 'fa',
    'direction'         => 'rtl',
    'calendar'          => 'jalali',
    'first_day_of_week' => 0,
    'status'            => true,
]);
```

### Update
```php
$language = Language::update($id, [
    'name'   => 'فارسی',
    'status' => true,
]);
```

### Delete
```php
Language::delete($id);
```

### Get / List
```php
// All languages
$languages = Language::all();

// Filtered, sorted, field-limited
$languages = Language::all([
    'status' => true,
]);
```

---

## Querying & Filters
If you expose a `query(array $filter = [])` method returning a `QueryBuilder`, you can compose queries fluently:

```php
$result = Language::query([
    'status' => true,
])->paginate(15);
```

---

## Events
Package emits domain events during lifecycle changes:
- `LanguageAddEvent` — after create
- `LanguageUpdatedEvent` — after update
- `LanguageDeletingEvent` — before delete
- `LanguageDeletedEvent` — after delete

---

## API Resources (Optional)
```php
return LanguageResource::collection($languages);
// or
return LanguageResource::make($language);
```

---

## Testing
Recommended coverage:
- **Rules:** `CheckLocaleRule`, `LanguageExistRule`
- **Service/Facade:** store/update/delete, query filters/sorts/fields
- **Events:** assert dispatched
- **Requests:** validation scenarios

---

## Contributing

Thank you for considering contributing to the Laravel Language! The contribution guide can be found in the [CONTRIBUTING.md](https://github.com/jobmetric/laravel-language/blob/master/CONTRIBUTING.md).

---

## License

The MIT License (MIT). Please see [License File](https://github.com/jobmetric/laravel-language/blob/master/LICENCE.md) for more information.
