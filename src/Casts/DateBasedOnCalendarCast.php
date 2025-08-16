<?php

namespace JobMetric\Language\Casts;

use BackedEnum;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use JobMetric\Language\Models\Language;
use JobMetric\MultiCalendar\Factory\CalendarConverterFactory;
use JobMetric\MultiCalendar\Helpers\NumberTransliterator;
use Throwable;

/**
 * Cast dates based on the current language calendar using jobmetric/multi-calendar.
 *
 * Storage:
 *  - Always stores in Gregorian as 'Y-m-d H:i:s' (app timezone).
 *
 * Get (DB -> Response):
 *  - Reads DB Gregorian, converts the Y-m-d part to the language calendar (using selected separator).
 *  - Appends time part (if mode is 'datetime') without calendar conversion.
 *  - Optionally transliterates digits (en|fa|ar).
 *
 * Set (Request -> DB):
 *  - If input is ISO-like Gregorian (e.g., '2025-08-16' or '2025-08-16 13:45:00'), parses directly.
 *  - Otherwise, treats input as language-calendar date string, splits by provided separator,
 *    converts to Gregorian via CalendarConverterFactory::make($calendar)->toGregorian(Y, m, d),
 *    keeps time part if present and mode is 'datetime'.
 *
 * Arguments (castUsing):
 *  - mode: 'date' or 'datetime' (default: 'date')
 *  - date_sep: '-', '/', '.' (default: '-')
 *  - digits: 'en' | 'fa' | 'ar' (default: 'en')
 *
 * Timezone:
 *  - Uses 'accept-timezone' request header, falls back to config('app.timezone').
 *
 * Fail-safe:
 *  - If language or converter is missing, falls back to plain Gregorian behavior.
 */
class DateBasedOnCalendarCast implements CastsAttributes
{
    /** @var 'date'|'datetime' */
    protected string $mode;

    /** @var string '-', '/', '.' */
    protected string $dateSep;

    /** @var 'en'|'fa'|'ar' */
    protected string $digits;

    /**
     * @param string $mode 'date' or 'datetime'
     * @param string $dateSep '-', '/', '.'
     * @param string $digits 'en'|'fa'|'ar'
     */
    public function __construct(string $mode = 'date', string $dateSep = '-', string $digits = 'en')
    {
        $this->mode = $mode === 'datetime' ? 'datetime' : 'date';
        $this->dateSep = in_array($dateSep, ['-', '/', '.'], true) ? $dateSep : '-';
        $this->digits = in_array($digits, ['en', 'fa', 'ar'], true) ? $digits : 'en';
    }

    /**
     * Cast the given value (DB -> Response).
     *
     * @param  Model  $model
     * @param  string $key
     * @param  mixed  $value
     * @param  array<string,mixed> $attributes
     * @return mixed
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        $appTz = (string) config('app.timezone');
        $clientTz = $this->safeTimezone((string) request()->header('accept-timezone', $appTz));

        // Normalize DB value (Gregorian) to Carbon in client timezone
        $dt = $this->toCarbon($value, $appTz)->setTimezone($clientTz);

        $lang = $this->currentLanguage();
        if (!$lang || empty($lang->calendar)) {
            // Gregorian fallback (format as chosen separator)
            $date = $dt->format('Y' . $this->dateSep . 'm' . $this->dateSep . 'd');
            $out = $this->mode === 'datetime' ? $date . ' ' . $dt->format('H:i:s') : $date;
            return $this->transliterate($out, $this->digits);
        }

        // Convert date part via multi-calendar
        [$y, $m, $d] = [$dt->year, $dt->month, $dt->day];

        try {
            $conv = CalendarConverterFactory::make((string) ($lang->calendar instanceof BackedEnum ? $lang->calendar->value : $lang->calendar));
            $date = $conv->fromGregorian($y, $m, $d, $this->dateSep);
        } catch (Throwable) {
            $date = $dt->format('Y' . $this->dateSep . 'm' . $this->dateSep . 'd');
        }

        $out = $this->mode === 'datetime' ? $date . ' ' . $dt->format('H:i:s') : $date;
        return $this->transliterate($out, $this->digits);
    }

    /**
     * Prepare the given value for storage (Request -> DB).
     *
     * @param  Model  $model
     * @param  string $key
     * @param  mixed  $value
     * @param  array<string,mixed> $attributes
     * @return mixed
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        $appTz    = (string) config('app.timezone');
        $clientTz = $this->safeTimezone((string) request()->header('accept-timezone', $appTz));

        // Fast-path for DateTime / timestamp
        if ($value instanceof DateTimeInterface || is_int($value) || (is_string($value) && ctype_digit(trim($value)))) {
            return $this->toCarbon($value, $clientTz)->setTimezone($appTz)->format('Y-m-d H:i:s');
        }

        $raw = trim((string) $value);
        if ($raw === '' || str_starts_with($raw, '0000-00-00')) {
            return null;
        }

        // 1) If we have a language calendar (non-gregorian), parse as HUMAN first.
        $language = $this->currentLanguage();
        if ($language && !empty($language->calendar)) {
            try {
                $calendarKey = $language->calendar instanceof BackedEnum
                    ? $language->calendar->value
                    : (string) $language->calendar;

                // Use jobmetric/multi-calendar
                $conv = CalendarConverterFactory::make($calendarKey);

                // split "date [time]" once
                $parts   = explode(' ', $raw, 2);
                $dateStr = $parts[0];
                $timeStr = $this->mode === 'datetime' && isset($parts[1]) ? trim($parts[1]) : null;

                // normalize digits to English before splitting
                $dateStrEn = $this->transliterate($dateStr, 'en');

                // accept -, / or . as separators; don't bind to a specific one
                $dateBits = preg_split('/[\/\-.]/', $dateStrEn);
                if (count($dateBits) === 3) {
                    [$y, $m, $d] = array_map('intval', $dateBits);

                    // HUMAN (language calendar) -> Gregorian
                    $greg = $conv->toGregorian($y, $m, $d); // [Y, m, d]
                    if (is_array($greg) && count($greg) === 3) {
                        [$gy, $gm, $gd] = array_map('intval', $greg);
                        $isoDate = sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
                        $iso     = $timeStr ? $isoDate.' '.$timeStr : $isoDate;

                        return $this->toCarbon($iso, $clientTz)->setTimezone($appTz)->format('Y-m-d H:i:s');
                    }
                }
            } catch (Throwable) {
                // fall through to Gregorian path
            }
        }

        // 2) Fallback: treat as Gregorian string
        return $this->toCarbon($raw, $clientTz)->setTimezone($appTz)->format('Y-m-d H:i:s');
    }


    /**
     * Factory-style usage for Laravel cast arguments.
     * Usage:
     *   'start_date'  => DateBasedOnCalendarCast::class . ':date,-,fa'
     *   'publish_at'  => DateBasedOnCalendarCast::class . ':datetime,/,en'
     *
     * @param  array<int,string> $arguments
     * @return static
     */
    public static function castUsing(array $arguments): static
    {
        $mode     = $arguments[0] ?? 'date';
        $dateSep  = $arguments[1] ?? '-';
        $digits   = $arguments[2] ?? 'en';

        return new self($mode, $dateSep, $digits);
    }

    /**
     * Try parsing as Gregorian (ISO/timestamp/Carbon).
     *
     * @param  mixed  $value
     * @param  string $tz
     * @return Carbon|null
     */
    protected function tryParseGregorian(mixed $value, string $tz): ?Carbon
    {
        try {
            if ($value instanceof DateTimeInterface || is_int($value)) {
                return $this->toCarbon($value, $tz);
            }

            if (is_string($value)) {
                $trim = trim($value);
                if ($trim === '' || Str::startsWith($trim, '0000-00-00')) {
                    return null;
                }

                // ISO-ish check
                if (preg_match('/^\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}:\d{2})?$/', $trim)) {
                    return $this->toCarbon($trim, $tz);
                }

                if (ctype_digit($trim)) {
                    return $this->toCarbon((int) $trim, $tz);
                }
            }

            return null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Normalize arbitrary value to Carbon in the given timezone.
     *
     * @param  mixed  $value
     * @param  string $tz
     * @return Carbon
     */
    protected function toCarbon(mixed $value, string $tz): Carbon
    {
        if ($value instanceof DateTimeInterface) {
            return Carbon::instance(Carbon::parse($value)->setTimezone($tz));
        }

        if (is_int($value)) {
            return Carbon::createFromTimestamp($value, $tz);
        }

        $string = (string) $value;
        if (ctype_digit($string)) {
            return Carbon::createFromTimestamp((int) $string, $tz);
        }

        return Carbon::parse($string, $tz);
    }

    /**
     * Get current Language model by app locale.
     *
     * @return Language|null
     */
    protected function currentLanguage(): ?Language
    {
        return Language::query()->where('locale', app()->getLocale())->first();
    }

    /**
     * Validate/normalize timezone name.
     *
     * @param  string $tz
     * @return string
     */
    protected function safeTimezone(string $tz): string
    {
        try {
            new \DateTimeZone($tz);
            return $tz;
        } catch (Throwable) {
            return (string) config('app.timezone');
        }
    }

    /**
     * Transliterate digits using Multi-Calendar helper.
     *
     * @param  string $str
     * @param  'en'|'fa'|'ar' $target
     * @return string
     */
    protected function transliterate(string $str, string $target): string
    {
        if (!in_array($target, ['en', 'fa', 'ar'], true)) {
            return $str;
        }

        try {
            return NumberTransliterator::trNum($str, $target);
        } catch (Throwable) {
            return $str;
        }
    }
}
