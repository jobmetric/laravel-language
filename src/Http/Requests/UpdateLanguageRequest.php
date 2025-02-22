<?php

namespace JobMetric\Language\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use JobMetric\Language\Enums\CalendarTypeEnum;
use JobMetric\Language\Rules\CheckLocaleRule;

class UpdateLanguageRequest extends FormRequest
{
    public int|null $language_id = null;
    public array $data = [];

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
        if (is_null($this->language_id)) {
            $language_id = $this->route()->parameter('language')->id;
        } else {
            $language_id = $this->language_id;
        }

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
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'name' => trans('language::base.form.fields.name.title'),
            'flag' => trans('language::base.form.fields.flag.title'),
            'locale' => trans('language::base.form.fields.locale.title'),
            'direction' => trans('language::base.form.fields.direction.title'),
            'calendar' => trans('language::base.form.fields.calendar.title'),
        ];
    }

    /**
     * Set language id for validation
     *
     * @param int $language_id
     * @return static
     */
    public function setLanguageId(int $language_id): static
    {
        $this->language_id = $language_id;

        return $this;
    }

    /**
     * Set data for validation
     *
     * @param array $data
     * @return static
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }
}
