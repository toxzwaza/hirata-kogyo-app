<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 作業実績更新リクエスト
 */
class UpdateWorkRecordRequest extends FormRequest
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
            // 手動レコードは drawing_id が null のため nullable とする
            'drawing_id' => ['nullable', 'exists:drawings,id'],
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
            'drawing_id.exists' => '選択された図番が存在しません。',
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
















