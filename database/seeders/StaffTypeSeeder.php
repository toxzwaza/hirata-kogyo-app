<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StaffTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('staff_types')->insert([
            ['name' => '正社員', 'created_at' => now(), 'updated_at' => now()],
            ['name' => '個人事業主', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
