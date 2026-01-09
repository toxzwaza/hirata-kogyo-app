<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'staff_type_id' => ['required', 'exists:staff_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'login_id' => ['required', 'string', 'max:255', 'unique:staff,login_id'],
            'password' => ['required', 'string', 'min:8'],
            'address' => ['nullable', 'string', 'max:255'],
            'tel' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'tax_type' => ['required', Rule::in(['taxable', 'tax_exempt'])],
            'active_flag' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'staff_type_id.required' => 'スタッフ種別を選択してください。',
            'staff_type_id.exists' => '選択されたスタッフ種別が存在しません。',
            'name.required' => '氏名を入力してください。',
            'login_id.required' => 'ログインIDを入力してください。',
            'login_id.unique' => 'このログインIDは既に使用されています。',
            'password.required' => 'パスワードを入力してください。',
            'password.min' => 'パスワードは8文字以上で入力してください。',
            'email.email' => 'メールアドレスの形式が正しくありません。',
            'tax_type.required' => '課税区分を選択してください。',
        ];
    }
}











