<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 客先請求書差額調整更新リクエスト
 */
class UpdateClientInvoiceAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'adjustment_amount' => ['required', 'numeric'],
            'adjustment_reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'adjustment_amount.required' => '差額調整金額を入力してください。',
            'adjustment_amount.numeric' => '差額調整金額は数値で入力してください。',
            'adjustment_reason.max' => '差額調整理由は255文字以内で入力してください。',
        ];
    }
}

