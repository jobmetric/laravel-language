<?php

namespace JobMetric\Language\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use JobMetric\Language\Models\Language;

class LanguageExistRule implements ValidationRule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(
        private readonly string $locale
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
        if ($value == 0) {
            return;
        }

        if (!Language::query()->where('id', $value)->where('locale', $this->locale)->exists()) {
            $fail(__('language::base.validation.language_exist', ['locale' => $this->locale]));
        }
    }
}
