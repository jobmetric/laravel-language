<?php

namespace JobMetric\Language\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use JobMetric\Language\Enums\CalendarTypeEnum;
use JobMetric\Language\Rules\CheckLocaleRule;

class UpdateLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array|string> */
    public function rules(): array
    {
        // Kept for Laravel pipeline usage (controllers)
        $language_id = $this->route()?->parameter('language')?->id;

        return static::rulesFor($this->all(), ['language_id' => $language_id]);
    }

    /**
     * Build rules with explicit context (used by dto()).
     *
     * @param array<string,mixed> $data
     * @param array<string,mixed> $ctx
     *
     * @return array<string, ValidationRule|array|string>
     */
    public static function rulesFor(array $data, array $ctx = []): array
    {
        $language_id = $ctx['language_id'] ?? null;

        return [
            'name' => 'string',
            'flag' => 'string|nullable',
            'locale' => [
                'string',
                new CheckLocaleRule($language_id)
            ],
            'direction' => 'string',
            'calendar' => 'string|in:' . implode(',', CalendarTypeEnum::values()),
            'status' => 'boolean',
        ];
    }

    /**
     * Normalize data outside Laravel pipeline (used by dto()).
     *
     * @param array<string,mixed> $data
     * @param array<string,mixed> $ctx
     * @return array<string,mixed>
     */
    public static function normalize(array $data, array $ctx = []): array
    {
        // Add any defaulting or transformations here if needed.
        // Example: trim strings, force nullable empties, etc.
        if (($data['flag'] ?? null) === '') {
            $data['flag'] = null;
        }

        return $data;
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
