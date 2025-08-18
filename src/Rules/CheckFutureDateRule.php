<?php

namespace JobMetric\Language\Rules;

use BackedEnum;
use Carbon\Carbon;
use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use JobMetric\Language\Models\Language;
use JobMetric\Language\Support\CurrentLanguage;
use JobMetric\MultiCalendar\Factory\CalendarConverterFactory;
use JobMetric\MultiCalendar\Helpers\NumberTransliterator;
use Throwable;

/**
 * Ensures the provided date/datetime value is in the future in the client's effective timezone.
 *
 * Usage intent:
 * - Designed for FormRequest rules to verify that a user-supplied date/time lies ahead of "now".
 * - The client's timezone is resolved by SetTimezoneMiddleware and exposed at config('app.client_timezone').
 * - Supports multi-calendar inputs (e.g., Jalali/Hijri/...), Persian/Arabic numerals, and '-', '/', '.' separators.
 * - Optionally bypasses validation when updating a model if the field value has not changed.
 */
class CheckFutureDateRule implements ValidationRule, ValidatorAwareRule
{
    /**
     * Holds the Validator instance to resolve displayable attribute names when building messages.
     *
     * @var Validator|null
     */
    private ?Validator $validator = null;

    /**
     * Fully-qualified model class name used for unchanged-value bypass on updates.
     *
     * @var string|null
     */
    private ?string $modelClass = null;

    /**
     * Primary key of the target model used for unchanged-value bypass.
     *
     * @var int|null
     */
    private ?int $modelId = null;

    /**
     * Attribute/column name of the target model to compare against the incoming value.
     *
     * @var string|null
     */
    private ?string $modelField = null;

    /**
     * Policy for date-only inputs: if true, interpret as 23:59:59; otherwise 00:00:00.
     *
     * @var bool
     */
    private bool $assumeEndOfDay;

    /**
     * Initialize the rule with optional unchanged-value bypass and date-only handling policy.
     *
     * @param string|null $modelClass Fully-qualified model class name for unchanged-value bypass
     * @param int|null $modelId Model primary key for unchanged-value bypass
     * @param string|null $modelField Attribute/column to compare for unchanged-value bypass
     * @param bool $assumeEndOfDay If true, date-only inputs are treated as 23:59:59
     */
    public function __construct(?string $modelClass = null, ?int $modelId = null, ?string $modelField = null, bool $assumeEndOfDay = false)
    {
        if ($modelClass && $modelId && $modelField) {
            $this->modelClass = $modelClass;
            $this->modelId = $modelId;
            $this->modelField = $modelField;
        }

        $this->assumeEndOfDay = $assumeEndOfDay;
    }

    /**
     * Provide the underlying Validator instance so we can resolve friendly attribute names.
     *
     * @param Validator $validator
     *
     * @return void
     */
    public function setValidator(Validator $validator): void
    {
        $this->validator = $validator;
    }

    /**
     * Validate that the given input is a future moment in the client's effective timezone.
     *
     * Behavior:
     * - Null/empty/zero-date values are ignored here so other rules (e.g., 'required') can handle them.
     * - If unchanged-value bypass is configured and the DB value is identical, validation passes.
     * - Input parsing respects the current language calendar and supports Persian/Arabic digits.
     *
     * @param string $attribute Attribute name being validated
     * @param mixed $value Incoming value (string|int|DateTimeInterface)
     * @param Closure(string): PotentiallyTranslatedString $fail Fail callback with translated message
     *
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $label = $this->validator
            ? $this->validator->getTranslator()->get($this->validator->customAttributes[$attribute] ?? $attribute)
            : $attribute;

        // Allow other rules like 'required' to handle empty.
        if ($value === null) {
            return;
        }
        if (is_string($value)) {
            $trim = trim($value);
            if ($trim === '' || str_starts_with($trim, '0000-00-00')) {
                return;
            }
        }

        // Unchanged-value bypass for updates.
        if ($this->modelClass && $this->modelId && $this->modelField) {
            /** @var Model|null $record */
            $record = $this->modelClass::query()->find($this->modelId);
            if ($record && !is_null($record->{$this->modelField}) && (string)$record->{$this->modelField} === (string)$value) {
                return;
            }
        }

        $clientTz = (string)(config('app.client_timezone') ?: config('app.timezone', 'UTC'));

        try {
            $candidate = $this->toCarbonInClientTz($value, $clientTz);

            if (!$candidate->isFuture()) {
                $fail(__('language::base.validation.future_date', ['attribute' => $label]));
            }
        } catch (Throwable) {
            // Swallow parse errors so format enforcement can be done by other rules.
            return;
        }
    }

    /**
     * Convert an arbitrary input to a Carbon instance in the client timezone.
     *
     * Accepted forms:
     * - DateTimeInterface
     * - UNIX timestamps (int or numeric string)
     * - Calendar-specific date strings (Y-m-d with -,/,. separators) optionally with time
     * - Gregorian-like fallbacks (ISO 8601, Y-m-d, Y/m/d, Y.m.d with optional time)
     *
     * @param mixed $value
     * @param string $clientTz
     *
     * @return Carbon
     */
    private function toCarbonInClientTz(mixed $value, string $clientTz): Carbon
    {
        if ($value instanceof DateTimeInterface) {
            return Carbon::instance(Carbon::parse($value))->setTimezone($clientTz);
        }

        if (is_int($value) || (is_string($value) && ctype_digit(trim($value)))) {
            return Carbon::createFromTimestamp((int)$value, $clientTz);
        }

        $raw = trim((string)$value);

        try {
            $rawEn = NumberTransliterator::trNum($raw, 'en');
        } catch (Throwable) {
            $rawEn = $raw;
        }

        $parts = explode(' ', $rawEn, 2);
        $dateStr = trim($parts[0]);
        $timeStr = isset($parts[1]) ? trim($parts[1]) : null;

        $iso = $this->calendarStringToGregorianIso($dateStr, $timeStr);

        $hasExplicitTime = $timeStr !== null
            || preg_match('/[Tt]\d{2}:\d{2}/', $rawEn) === 1
            || preg_match('/\d{2}:\d{2}/', $rawEn) === 1;

        $looksYmd = preg_match('/^\d{4}[\/\-.]\d{2}[\/\-.]\d{2}$/', $dateStr) === 1;

        if ($iso === null) {
            if (!$hasExplicitTime && $this->assumeEndOfDay && $looksYmd) {
                // Validate date part before appending end-of-day.
                [$yy, $mm, $dd] = array_map('intval', preg_split('/[\/\-.]/', $dateStr));
                if (!checkdate($mm, $dd, $yy)) {
                    throw new InvalidArgumentException('Invalid Y-m-d date.');
                }

                $parseStr = $dateStr . ' 23:59:59';
            } else {
                // Reject non-Gregorian-like inputs early.
                if (!$this->looksGregorianLike($rawEn)) {
                    throw new InvalidArgumentException('Unparseable date input.');
                }

                // If it "looks" like a plain date, validate it.
                if ($looksYmd) {
                    [$yy, $mm, $dd] = array_map('intval', preg_split('/[\/\-.]/', $dateStr));
                    if (!checkdate($mm, $dd, $yy)) {
                        throw new InvalidArgumentException('Invalid Y-m-d date.');
                    }
                }

                $parseStr = $rawEn;
            }
        } else {
            $parseStr = $iso;
        }

        return Carbon::parse($parseStr, $clientTz);
    }

    /**
     * Whitelist of gregorian-like formats for fallback parsing.
     *
     * Accepts:
     * - YYYY-MM-DD
     * - YYYY-MM-DD[ T]HH:MM[:SS][Z|±HH:MM]
     * - YYYY/MM/DD[ HH:MM[:SS]]
     * - YYYY.MM.DD[ HH:MM[:SS]]
     *
     * @param string $s
     *
     * @return bool
     */
    private function looksGregorianLike(string $s): bool
    {
        $patterns = [
            '/^\d{4}-\d{2}-\d{2}$/',
            '/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(?::\d{2})?(?:Z|[+-]\d{2}:\d{2})?$/',
            '/^\d{4}\/\d{2}\/\d{2}(?: \d{2}:\d{2}(?::\d{2})?)?$/',
            '/^\d{4}\.\d{2}\.\d{2}(?: \d{2}:\d{2}(?::\d{2})?)?$/',
        ];

        foreach ($patterns as $re) {
            if (preg_match($re, $s) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert a language-calendar date string (Y-m-d) to Gregorian ISO with optional time.
     *
     * Details:
     * - Accepts '-', '/', '.' as date separators.
     * - Uses CurrentLanguage to determine the calendar driver from the active locale.
     * - Returns 'Y-m-d[ H:i:s]' on success, or null on failure to allow graceful fallback.
     *
     * @param string $dateStr Date part in the language calendar (Y-m-d style)
     * @param string|null $timeStr Optional time part to append (HH:mm[:ss])
     *
     * @return string|null
     */
    private function calendarStringToGregorianIso(string $dateStr, ?string $timeStr): ?string
    {
        $bits = preg_split('/[\/\-.]/', $dateStr);
        if (!is_array($bits) || count($bits) !== 3) {
            return null;
        }

        // Reject non-digit parts to avoid "not-a-date" -> 0,0,0
        foreach ($bits as $b) {
            if (!ctype_digit($b)) {
                return null;
            }
        }

        [$yStr, $mStr, $dStr] = $bits;
        $y = (int)$yStr;
        $m = (int)$mStr;
        $d = (int)$dStr;

        // Basic range validation prior to conversion
        if ($y < 1 || $m < 1 || $m > 12 || $d < 1 || $d > 31) {
            return null;
        }

        /** @var Language|null $language */
        $language = CurrentLanguage::get() ?: Language::query()->where('locale', app()->getLocale())->first();
        if (!$language || empty($language->calendar)) {
            return null;
        }

        try {
            $calendarKey = $language->calendar instanceof BackedEnum ? $language->calendar->value : (string)$language->calendar;
            $conv = CalendarConverterFactory::make($calendarKey);

            $greg = $conv->toGregorian($y, $m, $d);
            if (!is_array($greg) || count($greg) !== 3) {
                return null;
            }

            [$gy, $gm, $gd] = array_map('intval', $greg);

            // Extra safety after conversion
            if ($gy < 1 || $gm < 1 || $gm > 12 || $gd < 1 || $gd > 31) {
                return null;
            }

            $isoDate = sprintf('%04d-%02d-%02d', $gy, $gm, $gd);

            // When no time provided, apply policy: start-of-day vs end-of-day
            if ($timeStr === null) {
                $timeStr = $this->assumeEndOfDay ? '23:59:59' : '00:00:00';
            }

            return $isoDate . ' ' . $timeStr;
        } catch (Throwable) {
            return null;
        }
    }
}
