<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 客先請求書作成リクエスト
 */
class CreateClientInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'staff_invoice_ids' => ['required', 'array', 'min:1'],
            'staff_invoice_ids.*' => ['required', 'exists:staff_invoices,id'],
            'period_from' => ['required', 'date'],
            'period_to' => ['required', 'date', 'after_or_equal:period_from'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => '客先を選択してください。',
            'client_id.exists' => '選択された客先が存在しません。',
            'staff_invoice_ids.required' => 'スタッフ請求書を1つ以上選択してください。',
            'staff_invoice_ids.array' => 'スタッフ請求書の選択形式が正しくありません。',
            'staff_invoice_ids.min' => 'スタッフ請求書を1つ以上選択してください。',
            'staff_invoice_ids.*.required' => 'スタッフ請求書を選択してください。',
            'staff_invoice_ids.*.exists' => '選択されたスタッフ請求書が存在しません。',
            'period_from.required' => '請求期間開始日を入力してください。',
            'period_from.date' => '請求期間開始日の形式が正しくありません。',
            'period_to.required' => '請求期間終了日を入力してください。',
            'period_to.date' => '請求期間終了日の形式が正しくありません。',
            'period_to.after_or_equal' => '請求期間終了日は開始日以降である必要があります。',
        ];
    }
}


