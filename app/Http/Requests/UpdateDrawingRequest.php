<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDrawingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'product_name' => ['required', 'string', 'max:255'],
            'drawing_number' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:10240'], // 10MB
            'weight_per_unit' => ['required', 'numeric', 'min:0'],
            'active_flag' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => '客先を選択してください。',
            'client_id.exists' => '選択された客先が存在しません。',
            'product_name.required' => '品名を入力してください。',
            'drawing_number.required' => '図番を入力してください。',
            'image.image' => '画像ファイルを選択してください。',
            'image.max' => '画像ファイルは10MB以下である必要があります。',
            'weight_per_unit.required' => '1個あたり重量を入力してください。',
            'weight_per_unit.numeric' => '1個あたり重量は数値で入力してください。',
            'weight_per_unit.min' => '1個あたり重量は0以上である必要があります。',
        ];
    }
}













