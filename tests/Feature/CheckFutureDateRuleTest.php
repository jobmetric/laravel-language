<?php

namespace JobMetric\Language\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use JobMetric\Language\Factories\LanguageFactory;
use JobMetric\Language\Rules\CheckFutureDateRule;
use JobMetric\Language\Tests\Stubs\Models\CastedItem;
use JobMetric\Language\Tests\TestCase;
use Throwable;

/**
 * @covers \JobMetric\Language\Rules\CheckFutureDateRule
 */
class CheckFutureDateRuleTest extends TestCase
{
    /**
     * Freeze time for deterministic assertions and reset after each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // App timezone is UTC from base TestCase
        Carbon::setTestNow(Carbon::parse('2025-08-16 12:00:00', 'UTC'));
        config()->set('app.client_timezone', 'UTC'); // middleware normally sets this
    }

    /**
     * Reset time travel.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Carbon::setTestNow(null);

        parent::tearDown();
    }

    /**
     * It passes for a future Jalali date and fails for a past Jalali date in client TZ.
     *
     * Reference: 2025-08-16 (Gregorian) == 1404-05-25 (Jalali)
     *
     * @return void
     */
    public function test_jalali_future_passes_and_past_fails(): void
    {
        app()->setLocale('fa');
        LanguageFactory::new()->persian()->create();

        $rule = new CheckFutureDateRule();

        // Future (1404-05-26 → 2025-08-17) should pass
        $v1 = ValidatorFacade::make(['date' => '1404-05-26'], ['date' => [$rule]]);
        $this->assertTrue($v1->passes(), 'Future Jalali date should pass');

        // Past (1404-05-24 → 2025-08-15) should fail
        $v2 = ValidatorFacade::make(['date' => '1404-05-24'], ['date' => [new CheckFutureDateRule()]]);
        $this->assertFalse($v2->passes(), 'Past Jalali date should fail');
        $this->assertArrayHasKey('date', $v2->errors()->toArray());
    }

    /**
     * It accepts Persian digits in input (date and datetime).
     *
     * @return void
     */
    public function test_accepts_persian_digits_in_input(): void
    {
        app()->setLocale('fa');
        LanguageFactory::new()->persian()->create();

        $rule = new CheckFutureDateRule();

        // Future: ۱۴۰۴-۰۵-۲۶ ۰۸:۰۰:۰۰ → 2025-08-17 08:00:00 (UTC)
        $v = ValidatorFacade::make(
            ['date' => '۱۴۰۴-۰۵-۲۶ ۰۸:۰۰:۰۰'],
            ['date' => [$rule]]
        );

        $this->assertTrue($v->passes());
    }

    /**
     * It accepts UNIX timestamps (int and numeric string) and evaluates future correctly.
     *
     * @return void
     */
    public function test_accepts_unix_timestamps(): void
    {
        // Now is 2025-08-16 12:00:00 UTC
        $futureTs = Carbon::parse('2025-08-16 12:00:01', 'UTC')->timestamp;
        $pastTs = Carbon::parse('2025-08-16 11:59:59', 'UTC')->timestamp;

        $rule = new CheckFutureDateRule();

        $v1 = ValidatorFacade::make(['date' => $futureTs], ['date' => [$rule]]);
        $this->assertTrue($v1->passes(), 'Future int timestamp should pass');

        $v2 = ValidatorFacade::make(['date' => (string)$futureTs], ['date' => [new CheckFutureDateRule()]]);
        $this->assertTrue($v2->passes(), 'Future numeric-string timestamp should pass');

        $v3 = ValidatorFacade::make(['date' => $pastTs], ['date' => [new CheckFutureDateRule()]]);
        $this->assertFalse($v3->passes(), 'Past timestamp should fail');
    }

    /**
     * Unchanged-value bypass: if model/id/field is configured and value equals stored,
     * the rule should pass without checking future/past.
     *
     * @return void
     * @throws Throwable
     */
    public function test_unchanged_value_bypass(): void
    {
        app()->setLocale('en');

        /** @var CastedItem $item */
        $item = new CastedItem();
        $item->datetime_at = '2025-08-16 12:00:00';
        $item->saveOrFail();

        $rule = new CheckFutureDateRule(CastedItem::class, $item->id, 'datetime_at');

        // Provide the same value as stored → should bypass and pass
        $v = ValidatorFacade::make(
            ['when' => '2025-08-16 12:00:00'],
            ['when' => [$rule]]
        );

        $this->assertTrue($v->passes());
    }

    /**
     * Date-only handling: when assumeEndOfDay=false, "today" (date-only) fails;
     * when assumeEndOfDay=true, "today" (date-only) passes (treated as 23:59:59).
     *
     * @return void
     */
    public function test_assume_end_of_day_policy_for_date_only_inputs(): void
    {
        app()->setLocale('en');
        // Now: 2025-08-16 12:00:00 UTC; client TZ: UTC

        // Default behavior (00:00:00) → past → fail
        $v1 = ValidatorFacade::make(['d' => '2025-08-16'], ['d' => [new CheckFutureDateRule()]]);
        $this->assertFalse($v1->passes(), 'Today@00:00:00 should be past at noon if not assuming EOD');

        // With assumeEndOfDay → interpret as 23:59:59 → future → pass
        $v2 = ValidatorFacade::make(['d' => '2025-08-16'], ['d' => [new CheckFutureDateRule(null, null, null, true)]]);
        $this->assertTrue($v2->passes(), 'Today@23:59:59 should pass when assuming end-of-day');
    }

    /**
     * It accepts '/', '.' as date separators for calendar input.
     *
     * @return void
     */
    public function test_accepts_various_date_separators(): void
    {
        app()->setLocale('fa');
        LanguageFactory::new()->persian()->create();

        // Future: 1404-05-26
        $rule = new CheckFutureDateRule();

        $v1 = ValidatorFacade::make(['date' => '1404/05/26'], ['date' => [$rule]]);
        $this->assertTrue($v1->passes(), 'Slash separator should be accepted');

        $v2 = ValidatorFacade::make(['date' => '1404.05.26'], ['date' => [new CheckFutureDateRule()]]);
        $this->assertTrue($v2->passes(), 'Dot separator should be accepted');
    }

    /**
     * Invalid strings should not throw and should be deferred to other rules
     * (this rule neither passes nor fails on format—here we assert "passes" to confirm no error).
     *
     * @return void
     */
    public function test_invalid_input_is_deferred_and_does_not_throw(): void
    {
        app()->setLocale('fa');
        LanguageFactory::new()->persian()->create();

        $v = ValidatorFacade::make(['date' => 'not-a-date'], ['date' => [new CheckFutureDateRule()]]);

        // Rule swallows parse errors and returns without failing;
        // format enforcement should be done by 'date'/'date_format' rules alongside.
        $this->assertTrue($v->passes());
    }
}
