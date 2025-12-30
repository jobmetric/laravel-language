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

**Build Language Management. Simply and Powerfully.**

Laravel Language simplifies language management in Laravel applications. Stop creating custom language tables manually and start building multilingual applications with confidence. It provides a clean, framework-native way to manage application languages with first-class validation rules, events, and a fluent query API—perfect for building global applications, e-commerce platforms, and content management systems. This is where powerful language management meets developer-friendly simplicity—giving you complete control over locales, calendars, and text directions without the complexity.

## Why Laravel Language?

### Simple API

Laravel Language provides a clean, intuitive API for managing languages. Store, update, delete, and query languages with simple method calls through the service or facade.

### Calendar Awareness

Support for multiple calendar systems: Gregorian, Jalali, Hijri, Hebrew, Buddhist, Coptic, Ethiopian, and Chinese. Each language can have its own calendar preference.

### Validation Rules

Built-in validation rules: `CheckLocaleRule`, `LanguageExistRule`, and `CheckFutureDateRule` ensure data integrity and validate locale codes and dates based on calendar systems.

### Middleware Support

Built-in middleware for setting language and timezone automatically based on user preferences or request parameters. No manual locale management needed.

## What is Language Management?

Language management is the process of managing multiple languages in your application, including locale settings, text direction (LTR/RTL), calendar systems, and formatting preferences. Traditional approaches often involve:

- Creating custom language tables manually
- Writing complex queries to filter and sort languages
- Managing locale settings manually
- Duplicating code across different parts of the application

Laravel Language solves these challenges by providing:

- **Unified System**: Single table for all language data
- **Calendar Support**: Multiple calendar systems out of the box
- **Simple API**: Clean methods for all operations
- **Event Integration**: Built-in events for extensibility
- **Query Helpers**: Easy methods for common queries

Consider a global e-commerce platform that needs to support Persian (Jalali calendar, RTL), Arabic (Hijri calendar, RTL), and English (Gregorian calendar, LTR). With Laravel Language, you can manage languages programmatically, set locales automatically through middleware, format dates based on calendar systems, handle text direction per language, and integrate with notification systems through events. The power of language management lies not only in supporting multiple languages but also in making it easy to manage calendars, directions, and formatting throughout your application.

## What Awaits You?

By adopting Laravel Language, you will:

- **Build multilingual applications** - Support multiple languages with different calendars and directions
- **Simplify language management** - Single API for all language operations
- **Support multiple calendars** - Gregorian, Jalali, Hijri, and more
- **Handle RTL/LTR automatically** - Text direction management built-in
- **Enable automatic locale detection** - Middleware handles locale and timezone
- **Maintain clean code** - Simple, intuitive API that follows Laravel conventions

## Quick Start

Install Laravel Language via Composer:

```bash
composer require jobmetric/laravel-language
```

Then publish the migration and run it:

```bash
php artisan vendor:publish --tag=language-migrations
php artisan migrate
```

## Documentation

Ready to transform your Laravel applications? Our comprehensive documentation is your gateway to mastering Laravel Language:

**[📚 Read Full Documentation →](https://jobmetric.github.io/packages/laravel-language/)**

The documentation includes:

- **Getting Started** - Quick introduction and installation guide
- **Language Service** - Core service for CRUD operations
- **Language Model** - Eloquent model with query scopes
- **Calendar Type Enum** - Calendar system types
- **Validation Rules** - Built-in rules for locale and language validation
- **Middleware** - Automatic locale and timezone setting
- **Events** - Hook into language lifecycle
- **Support Classes** - Helper functions for date formatting and timezone
- **Real-World Examples** - See how it works in practice

## Contributing

Thank you for participating in `laravel-language`. A contribution guide can be found [here](CONTRIBUTING.md).

## License

The `laravel-language` is open-sourced software licensed under the MIT license. See [License File](LICENCE.md) for more information.
