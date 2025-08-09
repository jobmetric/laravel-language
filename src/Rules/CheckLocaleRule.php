<?php

namespace JobMetric\Language\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use JobMetric\Language\Models\Language;

readonly class CheckLocaleRule implements ValidationRule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(
        private int|null $language_id = null
    )
    {
    }

    /**
     * Run the validation rule.
     *
     * @param Closure(string): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->language_id) {
            if (Language::query()->where('locale', $value)->where('id', '!=', $this->language_id)->exists()) {
                $fail(__('language::base.validation.locale', ['locale' => $value]));
            }

            return;
        }

        if (Language::query()->where('locale', $value)->exists()) {
            $fail(__('language::base.validation.locale', ['locale' => $value]));
        }
    }
}
