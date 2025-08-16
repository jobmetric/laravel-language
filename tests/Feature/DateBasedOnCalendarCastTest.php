<?php

namespace JobMetric\Language\Tests\Feature;

use Illuminate\Support\Facades\DB;
use JobMetric\Language\Factories\LanguageFactory;
use JobMetric\Language\Tests\Stubs\Models\CastedItem;
use JobMetric\Language\Tests\TestCase;

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
            'date_at'     => '2025-08-16 00:00:00',
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
            'date_at'     => '2025-08-16 00:00:00',
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

        $raw = \DB::table('casted_items')->where('id', $item->id)->first();
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
}
