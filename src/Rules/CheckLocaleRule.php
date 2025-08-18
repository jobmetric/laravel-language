<?php

namespace JobMetric\Language\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use JobMetric\Language\Models\Language;

/**
 * Class CheckLocaleRule
 *
 * Validates that the provided locale is unique and matches Laravel's folder convention.
 * Only two-letter lowercase codes are allowed (e.g., "fa", "en", "de").
 *
 * Behavior:
 * - Skips empty values to let 'required' or format rules handle them.
 * - Ensures the locale is exactly two lowercase letters.
 * - Checks uniqueness in the languages table, ignoring the current record if $languageId is provided.
 */
readonly class CheckLocaleRule implements ValidationRule
{
    private ?int $languageId;

    public function __construct(?int $languageId = null)
    {
        $this->languageId = $languageId;
    }

    /**
     * Run the validation rule.
     *
     * @param string $attribute
     * @param mixed $value
     * @param Closure(string): PotentiallyTranslatedString $fail
     *
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return;
        }

        $locale = trim((string) $value);

        // enforce two lowercase letters only
        if (!preg_match('/^[a-z]{2}$/', $locale)) {
            $fail(__('language::base.validation.locale_format', ['locale' => $locale]));
            return;
        }

        $exists = Language::query()
            ->when($this->languageId !== null, fn($q) => $q->where('id', '!=', $this->languageId))
            ->where('locale', $locale)
            ->exists();

        if ($exists) {
            $fail(__('language::base.validation.locale', ['locale' => $locale]));
        }
    }
}
