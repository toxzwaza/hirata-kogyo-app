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
                'name' => '山田 太郎',
                'login_id' => 'yamada',
                'password' => Hash::make('password'),
                'address' => '岡山県倉敷市〇〇町',
                'tel' => '090-1111-2222',
                'email' => 'yamada@example.com',
                'tax_type' => 'taxable',
                'active_flag' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'staff_type_id' => $contractorTypeId,
                'name' => '佐藤 花子',
                'login_id' => 'sato',
                'password' => Hash::make('password'),
                'address' => '岡山県岡山市〇〇区',
                'tel' => '090-3333-4444',
                'email' => 'sato@example.com',
                'tax_type' => 'tax_exempt',
                'active_flag' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
