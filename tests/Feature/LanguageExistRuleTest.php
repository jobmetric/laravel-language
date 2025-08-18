<?php

namespace JobMetric\Language\Tests\Feature;

use Illuminate\Support\Facades\Validator as ValidatorFacade;
use JobMetric\Language\Factories\LanguageFactory;
use JobMetric\Language\Rules\LanguageExistRule;
use JobMetric\Language\Tests\TestCase;

/**
 * @covers \JobMetric\Language\Rules\LanguageExistRule
 */
class LanguageExistRuleTest extends TestCase
{
    /**
     * It passes when value is 0 with default allowZero = true.
     *
     * @return void
     */
    public function test_zero_value_passes_when_allow_zero_default(): void
    {
        $v = ValidatorFacade::make(
            ['language_id' => 0],
            ['language_id' => [new LanguageExistRule('fa')]]
        );

        $this->assertTrue($v->passes());
    }

    /**
     * It fails when value is 0 and allowZero = false.
     *
     * @return void
     */
    public function test_zero_value_fails_when_allow_zero_false(): void
    {
        $v = ValidatorFacade::make(
            ['language_id' => 0],
            ['language_id' => [new LanguageExistRule('fa', false)]]
        );

        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('language_id', $v->errors()->toArray());
    }

    /**
     * It passes when the ID exists and locale matches.
     *
     * @return void
     */
    public function test_exists_and_locale_matches_passes(): void
    {
        $lang = LanguageFactory::new()->create(['locale' => 'fa']);

        $v1 = ValidatorFacade::make(
            ['language_id' => $lang->id],
            ['language_id' => [new LanguageExistRule('fa')]]
        );
        $v2 = ValidatorFacade::make(
            ['language_id' => (string) $lang->id],
            ['language_id' => [new LanguageExistRule('fa')]]
        );

        $this->assertTrue($v1->passes());
        $this->assertTrue($v2->passes());
    }

    /**
     * It fails when the ID exists but the locale does not match.
     *
     * @return void
     */
    public function test_exists_but_locale_mismatch_fails(): void
    {
        $fa = LanguageFactory::new()->create(['locale' => 'fa']);
        LanguageFactory::new()->create(['locale' => 'en']);

        $v = ValidatorFacade::make(
            ['language_id' => $fa->id],
            ['language_id' => [new LanguageExistRule('en')]]
        );

        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('language_id', $v->errors()->toArray());
    }

    /**
     * It fails when the value is non-numeric.
     *
     * @return void
     */
    public function test_non_numeric_value_fails(): void
    {
        $v = ValidatorFacade::make(
            ['language_id' => 'abc'],
            ['language_id' => [new LanguageExistRule('fa')]]
        );

        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('language_id', $v->errors()->toArray());
    }

    /**
     * It defers empty values to other rules (passes here).
     *
     * @return void
     */
    public function test_empty_value_is_ignored(): void
    {
        $v1 = ValidatorFacade::make(['language_id' => null], ['language_id' => [new LanguageExistRule('fa')]]);
        $v2 = ValidatorFacade::make(['language_id' => ''], ['language_id' => [new LanguageExistRule('fa')]]);
        $v3 = ValidatorFacade::make(['language_id' => '   '], ['language_id' => [new LanguageExistRule('fa')]]);

        $this->assertTrue($v1->passes());
        $this->assertTrue($v2->passes());
        $this->assertTrue($v3->passes());
    }
}
