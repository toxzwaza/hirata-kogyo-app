<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        // staff_types の ID を取得
        $employeeTypeId = DB::table('staff_types')->where('name', '正社員')->value('id');
        $contractorTypeId = DB::table('staff_types')->where('name', '個人事業主')->value('id');

        DB::table('staff')->insert([
            [
                'staff_type_id' => $employeeTypeId,
                'name' => '中尾省一',
                'name_kana' => 'ナカオショウイチ',
                'login_id' => 'nakao',
                'password' => Hash::make('password'),
                'postal_code' => '701-0204',
                'address' => '岡山市南区大福635-7',
                'tel' => null,
                'email' => null,
                'tax_type' => 'taxable',
                'active_flag' => true,
                'birth_date' => '1963-11-22',
                'bank_name' => 'おかやま信用金庫',
                'branch_name' => '操南支店',
                'account_type' => '普通',
                'account_number' => '0102142',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'staff_type_id' => $employeeTypeId,
                'name' => '岡竜司',
                'name_kana' => 'オカリュウジ',
                'login_id' => 'oka',
                'password' => Hash::make('password'),
                'postal_code' => null,
                'address' => null,
                'tel' => null,
                'email' => null,
                'tax_type' => 'taxable',
                'active_flag' => true,
                'birth_date' => '1981-04-09',
                'bank_name' => null,
                'branch_name' => null,
                'account_type' => null,
                'account_number' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'staff_type_id' => $contractorTypeId,
                'name' => '平田健',
                'name_kana' => 'ヒラタケン',
                'login_id' => 'hirata',
                'password' => Hash::make('password'),
                'postal_code' => '701-0204',
                'address' => '岡山市南区大福635-7',
                'tel' => null,
                'email' => null,
                'tax_type' => 'taxable',
                'active_flag' => true,
                'birth_date' => '1969-10-29',
                'bank_name' => 'おかやま信用金庫',
                'branch_name' => '操南支店',
                'account_type' => '普通',
                'account_number' => '0102142',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'staff_type_id' => $contractorTypeId,
                'name' => '木内勇希',
                'name_kana' => 'キナイユウキ',
                'login_id' => 'kinai',
                'password' => Hash::make('password'),
                'postal_code' => '701-0202',
                'address' => '岡山県岡山市南区山田847-15',
                'tel' => null,
                'email' => null,
                'tax_type' => 'taxable',
                'active_flag' => true,
                'birth_date' => '1994-10-22',
                'bank_name' => '中国銀行',
                'branch_name' => '妹尾支店',
                'account_type' => '普通',
                'account_number' => '1726232',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'staff_type_id' => $contractorTypeId,
                'name' => '武井佑希哉',
                'name_kana' => 'タケイユキヤ',
                'login_id' => 'takei',
                'password' => Hash::make('password'),
                'postal_code' => '710-1306',
                'address' => '岡山県倉敷市真備町有井1687−6',
                'tel' => null,
                'email' => null,
                'tax_type' => 'taxable',
                'active_flag' => true,
                'birth_date' => '1994-11-24',
                'bank_name' => 'ゆうちょ銀行',
                'branch_name' => '五四八店',
                'account_type' => null,
                'account_number' => '16321291',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
