<?php

namespace JobMetric\Language\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JobMetric\Language\Enums\CalendarTypeEnum;
use JobMetric\Language\Models\Language;

/**
 * LanguageFactory
 *
 * Generates consistent test data for the Language model:
 * - `flag`: randomly picked from `public/assets/vendor/language/flags/*.svg`
 *           and stored as a public-relative path (e.g., `assets/vendor/language/flags/iran.svg`).
 * - `locale`: defaults to `app()->getLocale()` (base code only, e.g., `fa`, `en`).
 * - `direction`: inferred from locale (rtl for fa/ar/he/ur).
 * - `calendar`: inferred from locale (fa→jalali, ar→hijri, he→hebrew, zh→chinese, else→gregorian).
 * - `first_day_of_week`: synchronized via Language::defaultFirstDayForCalendar().
 *
 * Quick states (languages):
 * - persian():  fa + jalali + rtl
 * - english():  en + gregorian + ltr
 * - arabic():   ar + hijri + rtl
 * - hebrew():   he + hebrew + rtl
 * - chinese():  zh + chinese + ltr
 *
 * Quick states (calendars only; do not change locale/name/direction):
 * - calendarGregorian()
 * - calendarJalali()
 * - calendarHijri()
 * - calendarHebrew()
 * - calendarBuddhist()
 * - calendarCoptic()
 * - calendarEthiopian()
 * - calendarChinese()
 *
 * @extends Factory<Language>
 */
class LanguageFactory extends Factory
{
    protected $model = Language::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $locale = app()->getLocale() ?: 'en';

        $calendar = match ($locale) {
            'fa' => CalendarTypeEnum::JALALI(),
            'ar' => CalendarTypeEnum::HIJRI(),
            'he' => CalendarTypeEnum::HEBREW(),
            'zh' => CalendarTypeEnum::CHINESE(),
            default => CalendarTypeEnum::GREGORIAN(),
        };

        $direction = in_array($locale, ['fa', 'ar', 'he', 'ur'], true)
            ? Language::DIRECTION_RTL
            : Language::DIRECTION_LTR;

        return [
            'name' => $this->guessLanguageName($locale),
            'flag' => $this->randomFlagPath(),
            'locale' => $locale,
            'direction' => $direction,
            'calendar' => $calendar,
            'first_day_of_week' => Language::defaultFirstDayForCalendar($calendar),
            'status' => true,
        ];
    }

    /**
     * Pick a random SVG flag from `public/assets/vendor/language/flags/*.svg`
     * and return a public-relative path (or null if no file exists).
     */
    protected function randomFlagPath(): ?string
    {
        $base = public_path('assets/vendor/language/flags');
        $files = glob($base . DIRECTORY_SEPARATOR . '*.svg');

        if (empty($files)) {
            return null;
        }

        $file = $this->faker->randomElement($files);
        $relative = str_replace(public_path() . DIRECTORY_SEPARATOR, '', $file);

        return str_replace(['\\', '//'], '/', $relative);
    }

    /**
     * Guess a human-readable language name for a base locale.
     */
    protected function guessLanguageName(string $locale): string
    {
        return match ($locale) {
            'fa' => 'Persian (Farsi)',
            'en' => 'English',
            'ar' => 'Arabic',
            'he' => 'Hebrew',
            'zh' => 'Chinese',
            default => ucfirst($locale),
        };
    }

    // --------------------------
    // Mutator-like states
    // --------------------------

    /** Set a custom display name. */
    public function setName(string $name): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => $name
        ]);
    }

    /**
     * Set a specific flag path. Accepts either a filename or a public-relative path.
     * Examples: 'iran.svg' or 'assets/vendor/language/flags/iran.svg'
     */
    public function setFlag(?string $flag): static
    {
        if ($flag && !str_contains($flag, 'assets/vendor/language/flags/')) {
            $flag = 'assets/vendor/language/flags/' . ltrim($flag, '/');
        }

        return $this->state(fn(array $attributes) => [
            'flag' => $flag
        ]);
    }

    /** Set a custom locale (base code only, e.g., 'fa'). */
    public function setLocale(string $locale): static
    {
        return $this->state(fn(array $attributes) => [
            'locale' => $locale
        ]);
    }

    /** Set a custom direction ('ltr'|'rtl'). */
    public function setDirection(string $direction): static
    {
        return $this->state(fn(array $attributes) => [
            'direction' => $direction
        ]);
    }

    /** Set a custom calendar and auto-sync the first day of week. */
    public function setCalendar(string $calendar): static
    {
        return $this->state(fn(array $attributes) => [
            'calendar' => $calendar,
            'first_day_of_week' => Language::defaultFirstDayForCalendar($calendar)
        ]);
    }

    /** Override first_day_of_week explicitly (0..6). */
    public function setFirstDayOfWeek(int $day): static
    {
        return $this->state(fn(array $attributes) => [
            'first_day_of_week' => $day
        ]);
    }

    /** Enable/disable status. */
    public function setStatus(bool $status): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => $status
        ]);
    }

    // --------------------------
    // Quick states (languages)
    // --------------------------

    public function persian(): static
    {
        $calendar = CalendarTypeEnum::JALALI();

        return $this->state(fn(array $attributes) => [
            'name' => 'Persian (Farsi)',
            'locale' => 'fa',
            'direction' => Language::DIRECTION_RTL,
            'calendar' => $calendar,
            'first_day_of_week' => Language::defaultFirstDayForCalendar($calendar)
        ]);
    }

    public function english(): static
    {
        $calendar = CalendarTypeEnum::GREGORIAN();

        return $this->state(fn(array $attributes) => [
            'name' => 'English',
            'locale' => 'en',
            'direction' => Language::DIRECTION_LTR,
            'calendar' => $calendar,
            'first_day_of_week' => Language::defaultFirstDayForCalendar($calendar)
        ]);
    }

    public function arabic(): static
    {
        $calendar = CalendarTypeEnum::HIJRI();

        return $this->state(fn(array $attributes) => [
            'name' => 'Arabic',
            'locale' => 'ar',
            'direction' => Language::DIRECTION_RTL,
            'calendar' => $calendar,
            'first_day_of_week' => Language::defaultFirstDayForCalendar($calendar)
        ]);
    }

    public function hebrew(): static
    {
        $calendar = CalendarTypeEnum::HEBREW();

        return $this->state(fn(array $attributes) => [
            'name' => 'Hebrew',
            'locale' => 'he',
            'direction' => Language::DIRECTION_RTL,
            'calendar' => $calendar,
            'first_day_of_week' => Language::defaultFirstDayForCalendar($calendar)
        ]);
    }

    public function chinese(): static
    {
        $calendar = CalendarTypeEnum::CHINESE();

        return $this->state(fn(array $attributes) => [
            'name' => 'Chinese',
            'locale' => 'zh',
            'direction' => Language::DIRECTION_LTR,
            'calendar' => $calendar,
            'first_day_of_week' => Language::defaultFirstDayForCalendar($calendar)
        ]);
    }

    // --------------------------
    // Quick states (calendars only)
    // --------------------------

    public function calendarGregorian(): static
    {
        $calendar = CalendarTypeEnum::GREGORIAN();

        return $this->state(fn(array $attributes) => [
            'calendar' => $calendar,
            'first_day_of_week' => Language::defaultFirstDayForCalendar($calendar)
        ]);
    }

    public function calendarJalali(): static
    {
        $calendar = CalendarTypeEnum::JALALI();

        return $this->state(fn(array $attributes) => [
            'calendar' => $calendar,
            'first_day_of_week' => Language::defaultFirstDayForCalendar($calendar)
        ]);
    }

    public function calendarHijri(): static
    {
        $calendar = CalendarTypeEnum::HIJRI();

        return $this->state(fn(array $attributes) => [
            'calendar' => $calendar,
            'first_day_of_week' => Language::defaultFirstDayForCalendar($calendar)
        ]);
    }

    public function calendarHebrew(): static
    {
        $calendar = CalendarTypeEnum::HEBREW();

        return $this->state(fn(array $attributes) => [
            'calendar' => $calendar,
            'first_day_of_week' => Language::defaultFirstDayForCalendar($calendar)
        ]);
    }

    public function calendarBuddhist(): static
    {
        $calendar = CalendarTypeEnum::BUDDHIST();

        return $this->state(fn(array $attributes) => [
            'calendar' => $calendar,
            'first_day_of_week' => Language::defaultFirstDayForCalendar($calendar)
        ]);
    }

    public function calendarCoptic(): static
    {
        $calendar = CalendarTypeEnum::COPTIC();

        return $this->state(fn(array $attributes) => [
            'calendar' => $calendar,
            'first_day_of_week' => Language::defaultFirstDayForCalendar($calendar)
        ]);
    }

    public function calendarEthiopian(): static
    {
        $calendar = CalendarTypeEnum::ETHIOPIAN();

        return $this->state(fn(array $attributes) => [
            'calendar' => $calendar,
            'first_day_of_week' => Language::defaultFirstDayForCalendar($calendar)
        ]);
    }

    public function calendarChinese(): static
    {
        $calendar = CalendarTypeEnum::CHINESE();

        return $this->state(fn(array $attributes) => [
            'calendar' => $calendar,
            'first_day_of_week' => Language::defaultFirstDayForCalendar($calendar)
        ]);
    }
}
