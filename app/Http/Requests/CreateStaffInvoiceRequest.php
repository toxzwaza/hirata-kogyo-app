<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * スタッフ請求書作成リクエスト
 */
class CreateStaffInvoiceRequest extends FormRequest
{
    /**
     * リクエストを承認するかどうか
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルール
     */
    public function rules(): array
    {
        return [
            'staff_id' => ['required', 'exists:staff,id'],
            'period_from' => ['required', 'date'],
            'period_to' => ['required', 'date', 'after_or_equal:period_from'],
        ];
    }

    /**
     * カスタムメッセージ
     */
    public function messages(): array
    {
        return [
            'staff_id.required' => 'スタッフを選択してください。',
            'staff_id.exists' => '選択されたスタッフが存在しません。',
            'period_from.required' => '請求期間開始日を入力してください。',
            'period_from.date' => '請求期間開始日の形式が正しくありません。',
            'period_to.required' => '請求期間終了日を入力してください。',
            'period_to.date' => '請求期間終了日の形式が正しくありません。',
            'period_to.after_or_equal' => '請求期間終了日は開始日以降である必要があります。',
        ];
    }
}













