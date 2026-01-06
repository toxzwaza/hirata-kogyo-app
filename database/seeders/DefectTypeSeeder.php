<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefectTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('defect_types')->insert([
            ['name' => '主・中子型不良', 'created_at' => now(), 'updated_at' => now()],
            ['name' => '砂型不良', 'created_at' => now(), 'updated_at' => now()],
            ['name' => '中子不良', 'created_at' => now(), 'updated_at' => now()],
            ['name' => '中子倒れ(浮き)', 'created_at' => now(), 'updated_at' => now()],
            ['name' => '型ズレ', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ノロ噛み', 'created_at' => now(), 'updated_at' => now()],
            ['name' => '砂噛み', 'created_at' => now(), 'updated_at' => now()],
            ['name' => '湯廻り不良', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ワレ', 'created_at' => now(), 'updated_at' => now()],
            ['name' => '打痕', 'created_at' => now(), 'updated_at' => now()],
            ['name' => '身喰い', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ガス欠陥', 'created_at' => now(), 'updated_at' => now()],
            ['name' => '仕上不良', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'その他(理由必要)', 'created_at' => now(), 'updated_at' => now()]
        ]);
    }
}
