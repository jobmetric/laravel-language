<?php

namespace JobMetric\Language\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use JobMetric\Language\Models\Language;

/**
 * Class LanguageExistRule
 *
 * Validates that a given language ID exists and matches the expected two-letter locale.
 *
 * Behavior:
 * - Skips null/empty values to let 'required' handle them.
 * - Allows 0 if $allowZero = true (default) — only when the value is truly 0 or "0".
 * - Ensures the ID exists in languages table with the given locale (two-letter lowercase).
 */
readonly class LanguageExistRule implements ValidationRule
{
    /**
     * Two-letter lowercase locale expected for the Language record.
     */
    private string $locale;

    /**
     * Whether zero (0) should bypass validation.
     */
    private bool $allowZero;

    /**
     * @param string $locale Two-letter lowercase locale (e.g., 'fa', 'en').
     * @param bool   $allowZero Allow 0 to bypass (default: true).
     */
    public function __construct(string $locale, bool $allowZero = true)
    {
        $this->locale    = strtolower(trim($locale));
        $this->allowZero = $allowZero;
    }

    /**
     * Run the validation rule.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param Closure(string): PotentiallyTranslatedString $fail
     *
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Defer empties to other rules
        if ($value === null) {
            return;
        }

        if (is_string($value)) {
            $valueTrimmed = trim($value);
            if ($valueTrimmed === '') {
                return;
            }

            // Allow zero only for exact "0"
            if ($this->allowZero && $valueTrimmed === '0') {
                return;
            }

            // Strict numeric: digit-only string
            if (!ctype_digit($valueTrimmed)) {
                $fail(__('language::base.validation.language_exist', ['locale' => $this->locale]));
                return;
            }

            $id = (int) $valueTrimmed;
        } else {
            // Non-string branch
            // Allow zero only for exact integer 0
            if ($this->allowZero && $value === 0) {
                return;
            }

            if (!is_int($value)) {
                $fail(__('language::base.validation.language_exist', ['locale' => $this->locale]));
                return;
            }

            $id = $value;
        }

        // At this point $id is a validated non-negative integer
        $exists = Language::query()
            ->where('id', $id)
            ->where('locale', $this->locale)
            ->exists();

        if (!$exists) {
            $fail(__('language::base.validation.language_exist', ['locale' => $this->locale]));
        }
    }
}
