<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffRequest extends FormRequest
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
            'name_kana' => ['nullable', 'string', 'max:255'],
            'login_id' => [
                'required',
                'string',
                'max:255',
                Rule::unique('staff', 'login_id')->ignore($this->staff),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'birth_date' => ['nullable', 'date'],
            'postal_code' => ['nullable', 'string', 'max:10', 'regex:/^\d{3}-?\d{4}$/'],
            'address' => ['nullable', 'string', 'max:500'],
            'tel' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'tax_type' => ['required', Rule::in(['taxable', 'tax_exempt'])],
            'active_flag' => ['boolean'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'branch_name' => ['nullable', 'string', 'max:255'],
            'account_type' => ['nullable', 'string', 'max:50'],
            'account_number' => ['nullable', 'string', 'max:50'],
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
            'password.min' => 'パスワードは8文字以上で入力してください。',
            'email.email' => 'メールアドレスの形式が正しくありません。',
            'tax_type.required' => '課税区分を選択してください。',
        ];
    }
}


