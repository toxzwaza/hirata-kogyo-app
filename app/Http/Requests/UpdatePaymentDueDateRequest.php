<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 支払い期限更新リクエスト（スタッフ請求書・客先請求書共通）
 */
class UpdatePaymentDueDateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_due_date' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_due_date.required' => '支払い期限を入力してください。',
            'payment_due_date.date' => '支払い期限は有効な日付で入力してください。',
        ];
    }
}
