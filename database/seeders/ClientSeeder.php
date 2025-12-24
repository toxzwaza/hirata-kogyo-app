<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = storage_path('app/public/移行用/客先.csv');
        $clients = [];

        if (file_exists($csvPath)) {
            $handle = fopen($csvPath, 'r');
            if ($handle !== false) {
                // 1行目（ヘッダー）をスキップ
                fgetcsv($handle);

                // CSVファイルの各行を読み込む
                while (($row = fgetcsv($handle)) !== false) {
                    if (!empty($row[0])) {
                        // 文字エンコーディングをShift-JISからUTF-8に変換
                        $name = mb_convert_encoding(trim($row[0]), 'UTF-8', 'SJIS-win');
                        
                        $clients[] = [
                            'name' => $name,
                            'name_kana' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
                fclose($handle);
            }
        }

        // データが存在する場合のみ挿入
        if (!empty($clients)) {
            DB::table('clients')->insert($clients);
        }
    }
}
