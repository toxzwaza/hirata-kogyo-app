<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkMethodSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('work_methods')->insert([
            ['name' => 'グラインダー', 'created_at' => now(), 'updated_at' => now()],
            ['name' => '手仕上げ', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
