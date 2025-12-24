<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'drawing_id' => ['required', 'exists:drawings,id'],
            'work_method_id' => ['required', 'exists:work_methods,id'],
            'rate_employee' => ['required', 'numeric', 'min:0'],
            'rate_contractor' => ['required', 'numeric', 'min:0'],
            'rate_overtime' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:1000'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after:effective_from'],
        ];
    }

    public function messages(): array
    {
        return [
            'drawing_id.required' => '図番を選択してください。',
            'drawing_id.exists' => '選択された図番が存在しません。',
            'work_method_id.required' => '作業方法を選択してください。',
            'work_method_id.exists' => '選択された作業方法が存在しません。',
            'rate_employee.required' => '正社員用単価を入力してください。',
            'rate_employee.numeric' => '正社員用単価は数値で入力してください。',
            'rate_contractor.required' => '個人事業主用通常単価を入力してください。',
            'rate_contractor.numeric' => '個人事業主用通常単価は数値で入力してください。',
            'rate_overtime.required' => '個人事業主用残業単価を入力してください。',
            'rate_overtime.numeric' => '個人事業主用残業単価は数値で入力してください。',
            'effective_from.required' => '適用開始日を入力してください。',
            'effective_from.date' => '適用開始日の形式が正しくありません。',
            'effective_to.date' => '適用終了日の形式が正しくありません。',
            'effective_to.after' => '適用終了日は適用開始日より後である必要があります。',
        ];
    }
}



