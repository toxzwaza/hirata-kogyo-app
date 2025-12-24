<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DrawingSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = storage_path('app/public/移行用/重複なし図番.csv');
        $drawings = [];

        if (file_exists($csvPath)) {
            $handle = fopen($csvPath, 'r');
            if ($handle !== false) {
                // 1行目（ヘッダー）をスキップ
                fgetcsv($handle);

                // 客先名からclient_idを取得するためのマップを作成
                $clientMap = DB::table('clients')
                    ->pluck('id', 'name')
                    ->toArray();

                // CSVファイルの各行を読み込む
                while (($row = fgetcsv($handle)) !== false) {
                    if (empty($row[0]) || empty($row[1])) {
                        continue; // 客先名または品番が空の場合はスキップ
                    }

                    $clientName = trim($row[0]);
                    $drawingNumber = trim($row[1]);
                    $productName = isset($row[2]) ? trim($row[2]) : '';
                    $weight = isset($row[3]) ? trim($row[3]) : '';

                    // 客先IDを取得
                    if (!isset($clientMap[$clientName])) {
                        // 客先が見つからない場合はスキップ（またはログに記録）
                        continue;
                    }
                    $clientId = $clientMap[$clientName];

                    // 重量の処理（"kg"を除去し、数値に変換）
                    $weightPerUnit = null;
                    if (!empty($weight)) {
                        // "kg"や"㎏"を除去
                        $weightCleaned = preg_replace('/[kKｋＫ][gGｇＧ]|㎏/u', '', $weight);
                        $weightCleaned = trim($weightCleaned);
                        if (is_numeric($weightCleaned)) {
                            $weightPerUnit = (float) $weightCleaned;
                        }
                    }

                    $drawings[] = [
                        'client_id' => $clientId,
                        'product_name' => $productName,
                        'drawing_number' => $drawingNumber,
                        'image_path' => null,
                        'weight_per_unit' => $weightPerUnit ?? 0,
                        'active_flag' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                fclose($handle);
            }
        }

        // データが存在する場合のみ挿入
        if (!empty($drawings)) {
            DB::table('drawings')->insert($drawings);
        }
    }
}

