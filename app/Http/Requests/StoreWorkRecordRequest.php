<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 作業実績登録リクエスト
 */
class StoreWorkRecordRequest extends FormRequest
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
        $isManual = $this->boolean('is_manual');

        return [
            'is_manual' => ['boolean'],
            // 通常モードは登録済み図番が必須。手動モードは品番テキストが必須
            'drawing_id' => [Rule::requiredIf(! $isManual), 'nullable', 'exists:drawings,id'],
            'manual_drawing_number' => [Rule::requiredIf($isManual), 'nullable', 'string', 'max:255'],
            'manual_product_name' => ['nullable', 'string', 'max:255'],
            'manual_client_name' => ['nullable', 'string', 'max:255'],
            'work_method_id' => ['required', 'exists:work_methods,id'],
            'staff_id' => ['required', 'exists:staff,id'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'quantity_good' => ['required', 'integer', 'min:0'],
            'memo' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * カスタムメッセージ
     */
    public function messages(): array
    {
        return [
            'drawing_id.required' => '図番を選択してください。',
            'drawing_id.exists' => '選択された図番が存在しません。',
            'manual_drawing_number.required' => '品番を入力してください。',
            'work_method_id.required' => '作業方法を選択してください。',
            'work_method_id.exists' => '選択された作業方法が存在しません。',
            'staff_id.required' => 'スタッフを選択してください。',
            'staff_id.exists' => '選択されたスタッフが存在しません。',
            'start_time.required' => '作業開始時刻を入力してください。',
            'start_time.date' => '作業開始時刻の形式が正しくありません。',
            'end_time.required' => '作業終了時刻を入力してください。',
            'end_time.date' => '作業終了時刻の形式が正しくありません。',
            'end_time.after' => '作業終了時刻は作業開始時刻より後である必要があります。',
            'quantity_good.required' => '良品数を入力してください。',
            'quantity_good.integer' => '良品数は整数で入力してください。',
            'quantity_good.min' => '良品数は0以上である必要があります。',
        ];
    }
}
















