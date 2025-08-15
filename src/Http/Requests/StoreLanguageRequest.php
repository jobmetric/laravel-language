<?php

namespace JobMetric\Language\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use JobMetric\Language\Enums\CalendarTypeEnum;
use JobMetric\Language\Rules\CheckLocaleRule;

class StoreLanguageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'string',
            'flag' => 'string|nullable',
            'locale' => [
                'string',
                new CheckLocaleRule
            ],
            'direction' => 'string|in:ltr,rtl',
            'calendar' => 'string|in:' . implode(',', CalendarTypeEnum::values()),
            'first_day_of_week' => 'sometimes|integer|between:0,6',
            'status' => 'boolean',
        ];
    }

    /**
     * Normalize raw input when validating outside Laravel's FormRequest pipeline.
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    public static function normalize(array $data): array
    {
        $data['first_day_of_week'] = $data['first_day_of_week'] ?? 0;

        if (($data['flag'] ?? null) === '') {
            $data['flag'] = null;
        }

        return $data;
    }

    /**
     * Laravel's native pipeline will still call this when using the FormRequest normally.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(static::normalize($this->all()));
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'name' => trans('language::base.fields.name'),
            'flag' => trans('language::base.fields.flag'),
            'locale' => trans('language::base.fields.locale'),
            'direction' => trans('language::base.fields.direction'),
            'calendar' => trans('language::base.fields.calendar'),
            'first_day_of_week' => trans('language::base.fields.first_day_of_week'),
            'status' => trans('language::base.fields.status'),
        ];
    }
}
