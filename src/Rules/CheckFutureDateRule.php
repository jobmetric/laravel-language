<?php

namespace JobMetric\Language\Rules;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Validation\Validator;
use JobMetric\Language\Enums\CalendarTypeEnum;
use JobMetric\Language\Facades\DateConverter;
use JobMetric\Language\Models\Language;

class CheckFutureDateRule implements ValidationRule, ValidatorAwareRule
{
    private ?Validator $validator = null;
    private string|null $model = null;
    private int|null $id = null;
    private string|null $field = null;

    /**
     * @param string|null $model
     * @param int|null $id
     * @param string|null $field
     */
    public function __construct(string $model = null, int $id = null, string $field = null)
    {
        if ($model && $id) {
            $this->model = $model;
            $this->id = $id;
            $this->field = $field;
        }
    }

    /**
     * Set the Validator instance.
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
     * Run the validation rule.
     *
     * @param Closure(string): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $translatedAttribute = $this->validator
            ? $this->validator->getTranslator()->get(
                $this->validator->customAttributes[$attribute] ?? $attribute
            )
            : $attribute;

        if ($this->model && $this->id) {
            /**
             * @var Model $model
             */
            $model = $this->model::query()->find($this->id);
            if ($model && !is_null($model->{$this->field}) && $model->{$this->field} === $value) {
                return;
            }
        }

        /**
         * @var Language $language
         */
        $language = Language::query()->where('locale', app()->getLocale())->first();
        if (!$language) {
            return;
        }

        if ($language->calendar === CalendarTypeEnum::JALALI()) {
            $dateAndTime = explode(' ', $value);
            $haveTime = count($dateAndTime) > 1;

            if (!isset($dateAndTime[0]) || $dateAndTime[0] === '0000-00-00') {
                return;
            }

            $date = explode('-', $dateAndTime[0]);

            if (count($date) !== 3) {
                return;
            }

            $value = DateConverter::jalaliToGregorian($date[0], $date[1], $date[2], '-');

            if ($haveTime) {
                $value .= ' ' . $dateAndTime[1];
            }
        }

        if (Carbon::make($value)->isPast()) {
            $fail(__('language::base.validation.future_date', ['attribute' => $translatedAttribute]));
        }
    }
}
