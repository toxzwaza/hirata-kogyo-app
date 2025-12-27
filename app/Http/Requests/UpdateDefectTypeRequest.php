<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDefectTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('defect_types', 'name')->ignore($this->defect_type),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '不良名を入力してください。',
            'name.unique' => 'この不良名は既に登録されています。',
        ];
    }
}









