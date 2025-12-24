<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefectTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('defect_types')->insert([
            ['name' => '寸法不良', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'キズ', 'created_at' => now(), 'updated_at' => now()],
            ['name' => '欠け', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'その他', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
