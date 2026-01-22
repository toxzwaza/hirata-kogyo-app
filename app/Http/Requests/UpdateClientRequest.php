<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 客先更新リクエスト
 */
class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'name_kana' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '客先名を入力してください。',
            'name.max' => '客先名は255文字以内で入力してください。',
            'name_kana.required' => '客先名フリガナを入力してください。',
            'name_kana.max' => '客先名フリガナは255文字以内で入力してください。',
        ];
    }
}














