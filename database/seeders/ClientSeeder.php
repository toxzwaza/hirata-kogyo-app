<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('clients')->insert([
            [
                'name' => '株式会社〇〇製作所',
                'name_kana' => 'カブシキガイシャマルマルセイサクショ',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '△△工業株式会社',
                'name_kana' => 'サンカクコウギョウカブシキガイシャ',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
