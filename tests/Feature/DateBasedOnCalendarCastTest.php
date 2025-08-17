<?php

namespace JobMetric\Language\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use JobMetric\Language\Factories\LanguageFactory;
use JobMetric\Language\Tests\Stubs\Models\CastedItem;
use JobMetric\Language\Tests\TestCase;
use Throwable;

/**
 * @covers \JobMetric\Language\Casts\DateBasedOnCalendarCast
 */
class DateBasedOnCalendarCastTest extends TestCase
{
    /**
     * It converts stored Gregorian to the current language calendar on "get"
     * for both date-only and datetime fields.
     *
     * @return void
     */
    public function test_get_converts_gregorian_to_language_calendar(): void
    {
        app()->setLocale('fa');
        LanguageFactory::new()->persian()->create();

        DB::table('casted_items')->insert([
            'date_at' => '2025-08-16 00:00:00',
            'datetime_at' => '2025-08-16 13:45:00',
        ]);

        /** @var CastedItem $item */
        $item = CastedItem::query()->firstOrFail();

        // 2025-08-16 (Gregorian) -> 1404-05-25 (Jalali)
        $this->assertSame('1404-05-25', $item->date_at);
        $this->assertSame('1404-05-25 13:45:00', $item->datetime_at);
    }

    /**
     * It parses language-calendar input on "set" and stores as Gregorian in DB.
     *
     * @return void
     */
    public function test_set_parses_language_calendar_to_gregorian_for_storage(): void
    {
        app()->setLocale('fa');
        LanguageFactory::new()->persian()->create();

        $item = new CastedItem();
        $item->date_at = '1404-05-25';
        $item->datetime_at = '1404-05-25 13:45:00';
        $item->save();

        $raw = DB::table('casted_items')->where('id', $item->id)->first();
        $this->assertSame('2025-08-16 00:00:00', $raw->date_at);
        $this->assertSame('2025-08-16 13:45:00', $raw->datetime_at);

        $fresh = CastedItem::query()->findOrFail($item->id);
        $this->assertSame('1404-05-25', $fresh->date_at);
        $this->assertSame('1404-05-25 13:45:00', $fresh->datetime_at);
    }

    /**
     * It falls back to Gregorian when no language is defined for the locale.
     *
     * @return void
     */
    public function test_fallback_to_gregorian_when_language_missing(): void
    {
        app()->setLocale('en'); // no Language row seeded for 'en'

        $item = new CastedItem();
        $item->date_at = '2025-08-16';
        $item->datetime_at = '2025-08-16 13:45:00';
        $item->save();

        $raw = DB::table('casted_items')->where('id', $item->id)->first();
        $this->assertSame('2025-08-16 00:00:00', $raw->date_at);
        $this->assertSame('2025-08-16 13:45:00', $raw->datetime_at);

        $fresh = CastedItem::query()->findOrFail($item->id);
        $this->assertSame('2025-08-16', $fresh->date_at);
        $this->assertSame('2025-08-16 13:45:00', $fresh->datetime_at);
    }

    /**
     * It respects the 'accept-timezone' header for output time on "get".
     *
     * @return void
     */
    public function test_accept_timezone_header_affects_output_time(): void
    {
        app()->setLocale('fa');
        LanguageFactory::new()->persian()->create();

        DB::table('casted_items')->insert([
            'date_at' => '2025-08-16 00:00:00',
            'datetime_at' => '2025-08-16 13:45:00',
        ]);

        request()->headers->set('accept-timezone', 'Asia/Tehran'); // UTC+03:30

        $item = CastedItem::query()->firstOrFail();

        $this->assertSame('1404-05-25', $item->date_at);
        $this->assertSame('1404-05-25 17:15:00', $item->datetime_at);

        request()->headers->remove('accept-timezone');
    }

    /**
     * It accepts ISO-like Gregorian input directly without calendar parsing.
     *
     * @return void
     */
    public function test_set_accepts_iso_like_gregorian_directly(): void
    {
        app()->setLocale('en');
        LanguageFactory::new()->english()->create();

        $item = new CastedItem();
        $item->date_at = '2025-08-16';
        $item->datetime_at = '2025-08-16 13:45:00';
        $item->save();

        $raw = DB::table('casted_items')->where('id', $item->id)->first();
        $this->assertSame('2025-08-16 00:00:00', $raw->date_at);
        $this->assertSame('2025-08-16 13:45:00', $raw->datetime_at);
    }


    /**
     * It treats zero-date inputs as null on "set".
     *
     * @return void
     */
    public function test_set_handles_zero_date_as_null(): void
    {
        app()->setLocale('fa');
        LanguageFactory::new()->persian()->create();

        $item = new CastedItem();
        $item->date_at = '0000-00-00';
        $item->datetime_at = '0000-00-00 00:00:00';
        $item->save();

        $raw = DB::table('casted_items')->where('id', $item->id)->first();
        $this->assertNull($raw->date_at);
        $this->assertNull($raw->datetime_at);
    }

    /**
     * It accepts Persian-digit inputs (date and datetime), converts to Gregorian for storage,
     * and returns human value using the cast's digit setting (here: 'en').
     *
     * @return void
     */
    public function test_set_accepts_persian_digits_in_input(): void
    {
        app()->setLocale('fa');
        LanguageFactory::new()->persian()->create();

        $item = new CastedItem;

        // Persian digits for date and datetime (note: ASCII colon is fine)
        $item->date_at = '۱۴۰۴-۰۵-۲۵';
        $item->datetime_at = '۱۴۰۴-۰۵-۲۵ ۱۳:۴۵:۰۰'; // mixed separator allowed on set()
        $item->save();

        // Raw DB must be pure Gregorian (Y-m-d H:i:s in app timezone)
        $raw = DB::table('casted_items')->where('id', $item->id)->first();
        $this->assertSame('2025-08-16 00:00:00', $raw->date_at);
        $this->assertSame('2025-08-16 13:45:00', $raw->datetime_at);

        // Human get must follow cast config: digits='en' and '-' separator in the stub model
        $fresh = CastedItem::query()->findOrFail($item->id);
        $this->assertSame('1404-05-25', $fresh->date_at);
        $this->assertSame('1404-05-25 13:45:00', $fresh->datetime_at);
    }

    /**
     * Verifies DST transition handling for Europe/London on "get" (timezone conversion only).
     *
     * Scenario:
     * - Store two UTC datetimes around the DST forward jump on 2025-03-30.
     * - With accept-timezone=Europe/London:
     *   00:30 UTC => 00:30 local (still GMT)
     *   01:30 UTC => 02:30 local (after the jump to BST).
     *
     * @return void
     */
    public function test_dst_transition_europe_london_on_get(): void
    {
        app()->setLocale('en'); // fallback to Gregorian; calendar conversion is not the focus here

        DB::table('casted_items')->insert([
            'date_at' => '2025-03-30 00:00:00',
            'datetime_at' => '2025-03-30 00:30:00',
        ]);

        DB::table('casted_items')->insert([
            'date_at' => '2025-03-30 00:00:00',
            'datetime_at' => '2025-03-30 01:30:00',
        ]);

        request()->headers->set('accept-timezone', 'Europe/London');

        $first = CastedItem::query()->findOrFail(1);
        $second = CastedItem::query()->findOrFail(2);

        $this->assertSame('2025-03-30 00:30:00', $first->datetime_at);
        $this->assertSame('2025-03-30 02:30:00', $second->datetime_at);

        request()->headers->remove('accept-timezone');
    }

    /**
     * Ensures invalid human input strings cause an exception during "set".
     *
     * Rationale:
     * - When calendar parsing fails and the fallback Gregorian parse also cannot understand
     *   the string, Carbon will throw. We surface that as a failure rather than silently storing bad data.
     *
     * @return void
     */
    public function test_set_with_invalid_input_string_throws_exception(): void
    {
        app()->setLocale('fa');
        LanguageFactory::new()->persian()->create();

        $this->expectException(Throwable::class);

        $item = new CastedItem();
        $item->date_at = 'date-invalid'; // Invalid date string
        $item->save();
    }

    /**
     * Accepts UNIX timestamps (int and numeric string) for both date and datetime on "set".
     *
     * Flow:
     * - Provide UNIX timestamp for 'date_at' (int) and for 'datetime_at' (string).
     * - Verify pure Gregorian storage and correct human-facing getters.
     *
     * @return void
     */
    public function test_set_accepts_unix_timestamp_and_numeric_string(): void
    {
        app()->setLocale('en'); // Gregorian fallback is fine

        $tsDate = Carbon::parse('2025-08-16 00:00:00', 'UTC')->timestamp;
        $tsDateTime = Carbon::parse('2025-08-16 13:45:00', 'UTC')->timestamp;

        $item = new CastedItem;
        $item->date_at = $tsDate;                 // int timestamp
        $item->datetime_at = (string)$tsDateTime; // numeric string timestamp
        $item->save();

        $raw = DB::table('casted_items')->where('id', $item->id)->first();
        $this->assertSame('2025-08-16 00:00:00', $raw->date_at);
        $this->assertSame('2025-08-16 13:45:00', $raw->datetime_at);

        $fresh = CastedItem::query()->findOrFail($item->id);
        $this->assertSame('2025-08-16', $fresh->date_at);
        $this->assertSame('2025-08-16 13:45:00', $fresh->datetime_at);
    }

    /**
     * It accepts slash and dot separators on "set" and stores correct Gregorian values.
     *
     * @return void
     */
    public function test_set_accepts_slash_and_dot_separators(): void
    {
        app()->setLocale('fa');
        LanguageFactory::new()->persian()->create();

        // Slash
        $item1 = new CastedItem();
        $item1->date_at = '۱۴۰۴/۰۵/۲۵';
        $item1->datetime_at = '۱۴۰۴/۰۵/۲۵ ۱۳:۴۵:۰۰';
        $item1->save();

        $raw1 = DB::table('casted_items')->where('id', $item1->id)->first();
        $this->assertSame('2025-08-16 00:00:00', $raw1->date_at);
        $this->assertSame('2025-08-16 13:45:00', $raw1->datetime_at);

        // Dot
        $item2 = new CastedItem();
        $item2->date_at = '۱۴۰۴.۰۵.۲۵';
        $item2->datetime_at = '۱۴۰۴.۰۵.۲۵ ۱۳:۴۵:۰۰';
        $item2->save();

        $raw2 = DB::table('casted_items')->where('id', $item2->id)->first();
        $this->assertSame('2025-08-16 00:00:00', $raw2->date_at);
        $this->assertSame('2025-08-16 13:45:00', $raw2->datetime_at);
    }

    /**
     * It accepts Arabic-Indic digits for date/time on "set" and persists correct Gregorian.
     *
     * @return void
     */
    public function test_set_accepts_arabic_indic_digits(): void
    {
        app()->setLocale('fa');
        LanguageFactory::new()->persian()->create();

        $item = new CastedItem();
        // Arabic-Indic digits: ١٤٠٤-٠٥-٢٥ ١٣:٤٥:٠٠
        $item->date_at = '١٤٠٤-٠٥-٢٥';
        $item->datetime_at = '١٤٠٤-٠٥-٢٥ ١٣:٤٥:٠٠';
        $item->save();

        $raw = DB::table('casted_items')->where('id', $item->id)->first();
        $this->assertSame('2025-08-16 00:00:00', $raw->date_at);
        $this->assertSame('2025-08-16 13:45:00', $raw->datetime_at);

        $fresh = CastedItem::query()->findOrFail($item->id);
        $this->assertSame('1404-05-25', $fresh->date_at);
        $this->assertSame('1404-05-25 13:45:00', $fresh->datetime_at);
    }

    /**
     * It allows datetime field to be saved with date-only input (defaults time to 00:00:00).
     *
     * @return void
     */
    public function test_datetime_mode_accepts_date_only_and_defaults_midnight(): void
    {
        app()->setLocale('fa');
        LanguageFactory::new()->persian()->create();

        $item = new CastedItem();
        $item->datetime_at = '1404-05-25'; // no time part
        $item->save();

        $raw = DB::table('casted_items')->where('id', $item->id)->first();
        $this->assertSame('2025-08-16 00:00:00', $raw->datetime_at);

        $fresh = CastedItem::query()->findOrFail($item->id);
        $this->assertSame('1404-05-25 00:00:00', $fresh->datetime_at);
    }

    /**
     * It falls back to app timezone when 'accept-timezone' header is invalid.
     *
     * @return void
     */
    public function test_invalid_timezone_header_falls_back_to_app_timezone(): void
    {
        app()->setLocale('fa');
        LanguageFactory::new()->persian()->create();

        // Store in app timezone (UTC per TestCase)
        DB::table('casted_items')->insert([
            'date_at' => '2025-08-16 00:00:00',
            'datetime_at' => '2025-08-16 12:00:00',
        ]);

        request()->headers->set('accept-timezone', 'Mars/Phobos'); // invalid TZ => fallback to app.tz

        $item = CastedItem::query()->firstOrFail();

        $this->assertSame('1404-05-25', $item->date_at);
        $this->assertSame('1404-05-25 12:00:00', $item->datetime_at);

        request()->headers->remove('accept-timezone');
    }
}
