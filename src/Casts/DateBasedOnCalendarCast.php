<?php

namespace JobMetric\Language\Casts;

use BackedEnum;
use Carbon\Carbon;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use JobMetric\Language\Models\Language;
use JobMetric\Language\Support\CurrentLanguage;
use JobMetric\MultiCalendar\Factory\CalendarConverterFactory;
use JobMetric\MultiCalendar\Helpers\NumberTransliterator;
use Throwable;

/**
 * Converts date/datetime attributes between storage (Gregorian) and the user's language calendar.
 *
 * Workflow:
 * - On get(): reads a Gregorian value from DB, converts only the Y-m-d part using the current language's calendar,
 *   keeps the time part as-is, applies chosen separator and digit transliteration.
 * - On set(): interprets the incoming human value primarily as the current language calendar (calendar-first),
 *   converts to Gregorian, normalizes timezone, and stores as 'Y-m-d H:i:s'.
 *
 * Key points:
 * - Storage is always Gregorian in app timezone.
 * - Input supports '-', '/', '.' separators and Persian/Arabic digits.
 * - When language or driver is missing, it falls back to Gregorian behavior.
 */
class DateBasedOnCalendarCast implements CastsAttributes
{
    /**
     * Controls whether the attribute is handled as a date-only value or a full datetime (affects time parsing/formatting).
     *
     * @var 'date'|'datetime'
     */
    protected string $mode;

    /**
     * Controls the date separator used in human-facing strings (e.g., "Y-m-d" vs "Y/m/d" vs "Y.m.d").
     *
     * @var string
     */
    protected string $dateSep;

    /**
     * Controls digit transliteration in human-facing strings (auto-resolved or explicit 'en'|'fa'|'ar').
     *
     * @var 'en'|'fa'|'ar'
     */
    protected string $digits;

    /**
     * Caches calendar converters per request keyed by calendar identifier to avoid repeated factory calls.
     *
     * @var array<string, mixed>
     */
    protected static array $converterCache = [];

    /**
     * Build the cast with runtime options for mode, date separator, and digit transliteration.
     *
     * Behavior impact:
     * - $mode affects whether a time part is expected/returned.
     * - $dateSep affects only the human-facing date rendering; input accepts -, /, . regardless.
     * - $digits controls output digits; set 'auto' in castUsing to follow current locale.
     *
     * @param string $mode
     * @param string $dateSep
     * @param string $digits
     */
    public function __construct(string $mode = 'date', string $dateSep = '-', string $digits = 'auto')
    {
        $this->mode = $mode === 'datetime' ? 'datetime' : 'date';
        $this->dateSep = in_array($dateSep, ['-', '/', '.'], true) ? $dateSep : '-';
        $this->digits = $this->resolveDigits($digits);
    }

    /**
     * Read a DB value (Gregorian) and convert it to a human string in the current language calendar.
     *
     * Flow:
     * 1) Guard null/zero-date.
     * 2) Normalize to Carbon in client timezone.
     * 3) If language calendar exists, convert Y-m-d using multi-calendar; otherwise format as Gregorian.
     * 4) Append time part for 'datetime' mode and apply digit transliteration.
     *
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @param array<string,mixed> $attributes
     *
     * @return string|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || (is_string($value) && str_starts_with($value, '0000-00-00'))) {
            return null;
        }

        $appTz = (string)config('app.timezone');
        $clientTz = $this->safeTimezone((string)request()->header('accept-timezone', $appTz));

        $dt = $this->toCarbon($value, $appTz)->setTimezone($clientTz);

        $language = $this->currentLanguage();
        if (!$language || empty($language->calendar)) {
            $date = $dt->format('Y' . $this->dateSep . 'm' . $this->dateSep . 'd');
            $out = $this->mode === 'datetime' ? $date . ' ' . $dt->format('H:i:s') : $date;

            return $this->transliterate($out, $this->digits);
        }

        [$y, $m, $d] = [$dt->year, $dt->month, $dt->day];

        try {
            $calendarKey = $language->calendar instanceof BackedEnum ? $language->calendar->value : (string)$language->calendar;
            $conv = $this->converterFor($calendarKey);
            $date = $conv->fromGregorian($y, $m, $d, $this->dateSep);
        } catch (Throwable) {
            $date = $dt->format('Y' . $this->dateSep . 'm' . $this->dateSep . 'd');
        }

        $out = $this->mode === 'datetime' ? $date . ' ' . $dt->format('H:i:s') : $date;

        return $this->transliterate($out, $this->digits);
    }

    /**
     * Take a human input (calendar-first) and convert it to a normalized Gregorian DB value.
     *
     * Flow:
     * 1) Guard null/zero-date.
     * 2) Fast-path for DateTimeInterface/timestamp (already Gregorian).
     * 3) If language calendar exists:
     *    - Split "date [time]" (accept -, /, .) and transliterate digits to English.
     *    - Convert Y-m-d from language calendar to Gregorian via converter.
     *    - Combine with time (if mode is 'datetime') and normalize timezone.
     * 4) Fallback: try parsing as Gregorian string (with digit normalization).
     *
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @param array<string,mixed> $attributes
     *
     * @return mixed
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        $appTz = (string)config('app.timezone');
        $clientTz = $this->safeTimezone((string)request()->header('accept-timezone', $appTz));

        if ($value instanceof DateTimeInterface || is_int($value) || (is_string($value) && ctype_digit(trim($value)))) {
            return $this->toCarbon($value, $clientTz)->setTimezone($appTz)->format('Y-m-d H:i:s');
        }

        $raw = trim((string)$value);
        if ($raw === '' || str_starts_with($raw, '0000-00-00')) {
            return null;
        }

        $language = $this->currentLanguage();
        if ($language && !empty($language->calendar)) {
            try {
                $calendarKey = $language->calendar instanceof BackedEnum ? $language->calendar->value : (string)$language->calendar;

                $parts = explode(' ', $raw, 2);
                $dateStr = trim($parts[0]);
                $timeStr = ($this->mode === 'datetime' && isset($parts[1])) ? trim($parts[1]) : null;

                $dateStrEn = $this->transliterate($dateStr, 'en');
                if ($timeStr !== null) {
                    $timeStr = $this->transliterate($timeStr, 'en');
                }

                $dateBits = preg_split('/[\/\-.]/', $dateStrEn);
                if (is_array($dateBits) && count($dateBits) === 3) {
                    [$y, $m, $d] = array_map('intval', $dateBits);

                    $conv = $this->converterFor($calendarKey);
                    $greg = $conv->toGregorian($y, $m, $d);

                    if (is_array($greg) && count($greg) === 3) {
                        [$gy, $gm, $gd] = array_map('intval', $greg);
                        $isoDate = sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
                        $iso = ($this->mode === 'datetime' && $timeStr) ? $isoDate . ' ' . $timeStr : $isoDate;

                        return $this->toCarbon($iso, $clientTz)->setTimezone($appTz)->format('Y-m-d H:i:s');
                    }
                }
            } catch (Throwable) {
                // fall through to Gregorian parse
            }
        }

        $rawEn = $this->transliterate($raw, 'en');

        return $this->toCarbon($rawEn, $clientTz)->setTimezone($appTz)->format('Y-m-d H:i:s');
    }

    /**
     * Allow passing mode/sep/digits via Eloquent cast string (e.g., ':datetime,/,fa').
     *
     * Effect:
     * - Instantiates the cast with per-attribute options for flexible formatting.
     *
     * @param array<int,string> $arguments
     *
     * @return static
     */
    public static function castUsing(array $arguments): static
    {
        $mode = $arguments[0] ?? 'date';
        $dateSep = $arguments[1] ?? '-';
        $digits = $arguments[2] ?? 'auto';

        return new self($mode, $dateSep, $digits);
    }

    /**
     * Normalize any supported input into Carbon under a given timezone.
     *
     * Accepts:
     * - DateTimeInterface instance
     * - UNIX timestamp (int|string)
     * - Parseable date/datetime string
     *
     * @param mixed $value
     * @param string $tz
     *
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

        $string = (string)$value;
        if (ctype_digit($string)) {
            return Carbon::createFromTimestamp((int)$string, $tz);
        }

        return Carbon::parse($string, $tz);
    }

    /**
     * Resolve the current language model using a per-request cache for performance.
     *
     * @return Language|null
     */
    protected function currentLanguage(): ?Language
    {
        return CurrentLanguage::get();
    }

    /**
     * Validate and normalize a timezone identifier, falling back to app timezone if invalid.
     *
     * @param string $tz
     *
     * @return string
     */
    protected function safeTimezone(string $tz): string
    {
        try {
            new DateTimeZone($tz);
            return $tz;
        } catch (Throwable) {
            return (string)config('app.timezone');
        }
    }

    /**
     * Convert digits in a string to the desired numeral system (English/Persian/Arabic).
     *
     * @param string $str
     * @param 'en'|'fa'|'ar'|'auto' $target
     *
     * @return string
     */
    protected function transliterate(string $str, string $target): string
    {
        $target = in_array($target, ['en', 'fa', 'ar'], true) ? $target : 'en';

        try {
            return NumberTransliterator::trNum($str, $target);
        } catch (Throwable) {
            return $str;
        }
    }

    /**
     * Determine the digit system to use based on explicit input or the current locale (auto).
     *
     * @param string|null $given
     *
     * @return 'en'|'fa'|'ar'
     */
    protected function resolveDigits(?string $given): string
    {
        if (in_array($given, ['en', 'fa', 'ar'], true)) {
            return $given;
        }

        $lang = $this->currentLanguage();

        return match (optional($lang)->locale) {
            'fa' => 'fa',
            'ar' => 'ar',
            default => 'en',
        };
    }

    /**
     * Retrieve (and cache) a calendar converter for the given calendar key.
     *
     * @param string $calendarKey
     *
     * @return mixed
     */
    protected function converterFor(string $calendarKey): mixed
    {
        if (!isset(self::$converterCache[$calendarKey])) {
            self::$converterCache[$calendarKey] = CalendarConverterFactory::make($calendarKey);
        }

        return self::$converterCache[$calendarKey];
    }
}
