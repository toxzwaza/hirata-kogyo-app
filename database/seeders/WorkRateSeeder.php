<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkRateSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = storage_path('app/public/移行用/データ移行用.csv');
        $workRates = [];

        if (file_exists($csvPath)) {
            $handle = fopen($csvPath, 'r');
            if ($handle !== false) {
                // 1行目（ヘッダー）をスキップ
                fgetcsv($handle);

                // 作業方法名からwork_method_idを取得するためのマップを作成
                $workMethodMap = DB::table('work_methods')
                    ->pluck('id', 'name')
                    ->toArray();

                // 図番IDを取得するためのクエリビルダーを準備
                // (client名 + drawing_numberで検索するため、事前に全drawingsを取得)
                $drawings = DB::table('drawings')
                    ->join('clients', 'drawings.client_id', '=', 'clients.id')
                    ->select('drawings.id', 'drawings.drawing_number', 'clients.name as client_name')
                    ->get();

                // (client_name, drawing_number) => drawing_id のマップを作成
                $drawingMap = [];
                foreach ($drawings as $drawing) {
                    $key = $drawing->client_name . '|' . $drawing->drawing_number;
                    $drawingMap[$key] = $drawing->id;
                }

                // CSVファイルの各行を読み込む
                while (($row = fgetcsv($handle)) !== false) {
                    if (empty($row[0]) || empty($row[1]) || empty($row[2])) {
                        continue; // 作業種類、客先、品番が空の場合はスキップ
                    }

                    $workMethodName = trim($row[0]);
                    $clientName = trim($row[1]);
                    $drawingNumber = trim($row[2]);
                    $rateContractor = isset($row[4]) ? trim($row[4]) : '';
                    $rateOvertime = isset($row[5]) ? trim($row[5]) : '';
                    $rateEmployee = isset($row[6]) ? trim($row[6]) : '';
                    $note = isset($row[8]) ? trim($row[8]) : '';

                    // 作業方法IDを取得
                    if (!isset($workMethodMap[$workMethodName])) {
                        continue; // 作業方法が見つからない場合はスキップ
                    }
                    $workMethodId = $workMethodMap[$workMethodName];

                    // 図番IDを取得
                    $drawingKey = $clientName . '|' . $drawingNumber;
                    if (!isset($drawingMap[$drawingKey])) {
                        continue; // 図番が見つからない場合はスキップ
                    }
                    $drawingId = $drawingMap[$drawingKey];

                    // 単価の処理（空の場合はnull）
                    $rateContractorValue = null;
                    if (!empty($rateContractor) && is_numeric($rateContractor)) {
                        $rateContractorValue = (float) $rateContractor;
                    }

                    $rateOvertimeValue = null;
                    if (!empty($rateOvertime) && is_numeric($rateOvertime)) {
                        $rateOvertimeValue = (float) $rateOvertime;
                    }

                    $rateEmployeeValue = null;
                    if (!empty($rateEmployee) && is_numeric($rateEmployee)) {
                        $rateEmployeeValue = (float) $rateEmployee;
                    }

                    $workRates[] = [
                        'drawing_id' => $drawingId,
                        'work_method_id' => $workMethodId,
                        'rate_employee' => $rateEmployeeValue,
                        'rate_contractor' => $rateContractorValue,
                        'rate_overtime' => $rateOvertimeValue,
                        'note' => !empty($note) ? $note : null,
                        'effective_from' => now()->format('Y-m-d'),
                        'effective_to' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                fclose($handle);
            }
        }

        // データが存在する場合のみ挿入
        if (!empty($workRates)) {
            DB::table('work_rates')->insert($workRates);
        }
    }
}

