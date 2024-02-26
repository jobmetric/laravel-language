<?php

namespace JobMetric\Language\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
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
            'direction' => 'string',
            'status' => 'boolean',
        ];
    }
}
