<?php

namespace JobMetric\Language\Tests\Unit;

use Carbon\Carbon;
use DateTimeImmutable;
use DateTimeZone;
use JobMetric\Language\Tests\TestCase;
use Throwable;

/**
 * @covers ::client_timezone
 * @covers ::tz_format
 * @covers ::tz_carbon
 */
class TimezoneHelpersTest extends TestCase
{
    /**
     * It returns app.client_timezone when present (as set by middleware).
     *
     * @return void
     */
    public function test_client_timezone_prefers_app_client_timezone(): void
    {
        config()->set('app.timezone', 'UTC');
        config()->set('app.client_timezone', 'Asia/Tehran');

        $this->assertSame('Asia/Tehran', client_timezone());
    }

    /**
     * It falls back to app.timezone when app.client_timezone is missing.
     *
     * @return void
     */
    public function test_client_timezone_falls_back_to_app_timezone(): void
    {
        config()->set('app.timezone', 'UTC');
        config()->offsetUnset('app.client_timezone');

        $this->assertSame('UTC', client_timezone());
    }

    /**
     * It converts a UTC datetime string to client timezone via tz_format.
     *
     * @return void
     */
    public function test_tz_format_converts_from_utc_to_client_timezone(): void
    {
        config()->set('app.timezone', 'UTC');
        config()->set('app.client_timezone', 'Asia/Tehran');

        $out = tz_format('2025-08-16 12:00:00');

        $this->assertSame('2025-08-16 15:30:00', $out);
    }

    /**
     * It accepts UNIX timestamp (int) and numeric-string timestamp on tz_format.
     *
     * @return void
     */
    public function test_tz_format_accepts_int_and_numeric_string_timestamps(): void
    {
        config()->set('app.timezone', 'UTC');
        config()->set('app.client_timezone', 'Europe/London');

        $tsInt = Carbon::parse('2025-03-30 01:30:00', 'UTC')->timestamp;
        $tsStr = (string)Carbon::parse('2025-03-30 01:30:00', 'UTC')->timestamp;

        // 2025-03-30 01:30 UTC => 02:30 Europe/London (DST in effect)
        $this->assertSame('2025-03-30 02:30:00', tz_format($tsInt));
        $this->assertSame('2025-03-30 02:30:00', tz_format($tsStr));
    }

    /**
     * It respects explicit target tz parameter over client timezone on tz_format.
     *
     * @return void
     */
    public function test_tz_format_respects_explicit_target_timezone(): void
    {
        config()->set('app.timezone', 'UTC');
        config()->set('app.client_timezone', 'Asia/Tehran');

        $out = tz_format('2025-08-16 12:00:00', 'Y-m-d H:i:s', 'America/New_York');

        // 12:00 UTC => 08:00 America/New_York (EDT, UTC-4)
        $this->assertSame('2025-08-16 08:00:00', $out);
    }

    /**
     * It respects explicit source timezone (fromTz) on tz_format.
     *
     * @return void
     */
    public function test_tz_format_respects_from_timezone_parameter(): void
    {
        config()->set('app.timezone', 'UTC');
        config()->set('app.client_timezone', 'UTC');

        // Interpret the string as America/New_York, then format in UTC.
        $out = tz_format('2025-08-16 13:45:00', 'Y-m-d H:i:s', null, 'America/New_York');

        // 2025-08-16 13:45 in New York (EDT, UTC-4) => 17:45 UTC
        $this->assertSame('2025-08-16 17:45:00', $out);
    }

    /**
     * It returns a Carbon instance in the client timezone via tz_carbon.
     *
     * @return void
     */
    public function test_tz_carbon_returns_carbon_in_client_timezone(): void
    {
        config()->set('app.timezone', 'UTC');
        config()->set('app.client_timezone', 'Asia/Tehran');

        $c = tz_carbon('2025-08-16 12:00:00');

        $this->assertInstanceOf(Carbon::class, $c);
        $this->assertSame('Asia/Tehran', $c->getTimezone()->getName());
        $this->assertSame('2025-08-16T15:30:00+03:30', $c->toIso8601String());
    }

    /**
     * It accepts DateTimeInterface input on tz_carbon and converts to client timezone.
     *
     * @return void
     * @throws Throwable
     */
    public function test_tz_carbon_accepts_datetime_interface(): void
    {
        config()->set('app.timezone', 'UTC');
        config()->set('app.client_timezone', 'Europe/London');

        $dt = new DateTimeImmutable('2025-03-30 01:30:00', new DateTimeZone('UTC'));
        $c = tz_carbon($dt);

        $this->assertSame('Europe/London', $c->getTimezone()->getName());
        $this->assertSame('2025-03-30 02:30:00', $c->format('Y-m-d H:i:s'));
    }

    /**
     * Helpers do not mutate app.timezone or PHP default timezone.
     *
     * @return void
     */
    public function test_helpers_do_not_mutate_app_or_php_timezone(): void
    {
        config()->set('app.timezone', 'UTC');
        config()->set('app.client_timezone', 'Asia/Tehran');

        $initialPhpTz = date_default_timezone_get();

        // Call helpers
        tz_format('2025-08-16 12:00:00');
        tz_carbon('2025-08-16 12:00:00');

        $this->assertSame('UTC', config('app.timezone'));
        $this->assertSame($initialPhpTz, date_default_timezone_get());
    }
}
