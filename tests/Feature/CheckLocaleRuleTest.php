<?php

namespace JobMetric\Language\Tests\Feature;

use Illuminate\Support\Facades\Validator as ValidatorFacade;
use JobMetric\Language\Factories\LanguageFactory;
use JobMetric\Language\Rules\CheckLocaleRule;
use JobMetric\Language\Tests\TestCase;

/**
 * @covers \JobMetric\Language\Rules\CheckLocaleRule
 */
class CheckLocaleRuleTest extends TestCase
{
    /**
     * It fails when the locale format is not exactly two lowercase letters.
     *
     * @return void
     */
    public function test_invalid_format_is_rejected(): void
    {
        $cases = [
            'FA',      // uppercase
            'fa-IR',   // with region
            'fa_IR',   // underscore with region
            'f',       // too short
            'eng',     // too long
            'en1',     // contains digit
            ' e n ',   // spaces
        ];

        foreach ($cases as $bad) {
            $v = ValidatorFacade::make(
                ['locale' => $bad],
                ['locale' => ['string', new CheckLocaleRule()]]
            );

            $this->assertFalse($v->passes(), "Locale [$bad] should be rejected by format rule.");
            $this->assertArrayHasKey('locale', $v->errors()->toArray());
        }
    }

    /**
     * It passes when the locale is valid two-letter lowercase and unique (create).
     *
     * @return void
     */
    public function test_valid_unique_on_create_passes(): void
    {
        $v = ValidatorFacade::make(
            ['locale' => 'en'],
            ['locale' => ['required', 'string', new CheckLocaleRule()]]
        );

        $this->assertTrue($v->passes());
    }

    /**
     * It fails on create when a duplicate lowercase locale exists.
     *
     * @return void
     */
    public function test_duplicate_on_create_fails(): void
    {
        LanguageFactory::new()->create(['locale' => 'fa']);

        $v = ValidatorFacade::make(
            ['locale' => 'fa'],
            ['locale' => ['required', 'string', new CheckLocaleRule()]]
        );

        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('locale', $v->errors()->toArray());
    }

    /**
     * It ignores the current record on update when locale is unchanged.
     *
     * @return void
     */
    public function test_update_ignores_same_record(): void
    {
        $lang = LanguageFactory::new()->create(['locale' => 'fa']);

        $v = ValidatorFacade::make(
            ['locale' => 'fa'],
            ['locale' => ['required', 'string', new CheckLocaleRule($lang->id)]]
        );

        $this->assertTrue($v->passes());
    }

    /**
     * It defers empty values to other rules (passes here).
     *
     * @return void
     */
    public function test_empty_value_is_ignored(): void
    {
        $v1 = ValidatorFacade::make(['locale' => null], ['locale' => [new CheckLocaleRule()]]);
        $v2 = ValidatorFacade::make(['locale' => ''], ['locale' => [new CheckLocaleRule()]]);
        $v3 = ValidatorFacade::make(['locale' => '   '], ['locale' => [new CheckLocaleRule()]]);

        $this->assertTrue($v1->passes());
        $this->assertTrue($v2->passes());
        $this->assertTrue($v3->passes());
    }
}
