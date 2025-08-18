<?php

use Carbon\Carbon;

if (!function_exists('client_timezone')) {
    /**
     * Get the effective client timezone resolved by SetTimezoneMiddleware.
     *
     * Order:
     * 1) config('app.client_timezone')   ← set by middleware per request
     * 2) config('app.timezone')          ← app fallback (usually 'UTC')
     *
     * @return string
     */
    function client_timezone(): string
    {
        $tz = config('app.client_timezone');
        if (is_string($tz) && $tz !== '') {
            return $tz;
        }

        return (string)config('app.timezone', 'UTC');
    }
}

if (!function_exists('tz_format')) {
    /**
     * Format a date/time value into the client's timezone without mutating storage conventions.
     *
     * Behavior:
     * - Parses the input using $fromTz (default: config('app.timezone'), typically UTC).
     * - Converts to the desired target timezone (default: client_timezone()).
     * - Formats using the provided $format.
     *
     * @param Carbon|DateTimeInterface|int|string $value
     * @param string $format
     * @param string|null $tz
     * @param string|null $fromTz
     *
     * @return string
     */
    function tz_format(Carbon|DateTimeInterface|int|string $value, string $format = 'Y-m-d H:i:s', ?string $tz = null, ?string $fromTz = null): string
    {
        $from = $fromTz ?: (string)config('app.timezone', 'UTC');
        $to = $tz ?: client_timezone();

        if ($value instanceof DateTimeInterface) {
            $dt = Carbon::instance(Carbon::parse($value)->setTimezone($from));
        } elseif (is_int($value) || (is_string($value) && ctype_digit($value))) {
            $dt = Carbon::createFromTimestamp((int)$value, $from);
        } else {
            $dt = Carbon::parse((string)$value, $from);
        }

        return $dt->setTimezone($to)->format($format);
    }
}

if (!function_exists('tz_carbon')) {
    /**
     * Convert an input into a Carbon instance adjusted to the client's timezone.
     *
     * @param Carbon|DateTimeInterface|int|string $value
     * @param string|null $tz
     * @param string|null $fromTz
     *
     * @return Carbon
     */
    function tz_carbon(Carbon|DateTimeInterface|int|string $value, ?string $tz = null, ?string $fromTz = null): Carbon
    {
        $from = $fromTz ?: (string)config('app.timezone', 'UTC');
        $to = $tz ?: client_timezone();

        if ($value instanceof DateTimeInterface) {
            $dt = Carbon::instance(Carbon::parse($value)->setTimezone($from));
        } elseif (is_int($value) || (is_string($value) && ctype_digit($value))) {
            $dt = Carbon::createFromTimestamp((int)$value, $from);
        } else {
            $dt = Carbon::parse((string)$value, $from);
        }

        return $dt->setTimezone($to);
    }
}
