<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:work_methods,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '作業方法名を入力してください。',
            'name.unique' => 'この作業方法名は既に登録されています。',
        ];
    }
}











