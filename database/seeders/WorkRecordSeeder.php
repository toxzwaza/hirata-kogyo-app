<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Drawing;
use App\Models\WorkMethod;
use Carbon\Carbon;

class WorkRecordSeeder extends Seeder
{
    public function run(): void
    {
        // スタッフを取得
        $staffList = DB::table('staff')
            ->where('active_flag', true)
            ->get();

        // 図番を取得
        $drawings = Drawing::where('active_flag', true)->get();
        if ($drawings->isEmpty()) {
            $this->command->warn('有効な図番が見つかりません。シーディングをスキップします。');
            return;
        }

        // 作業方法を取得
        $workMethods = WorkMethod::all();
        if ($workMethods->isEmpty()) {
            $this->command->warn('作業方法が見つかりません。シーディングをスキップします。');
            return;
        }

        // 期間設定：2025/11/21-2025/12/20
        $startDate = Carbon::parse('2025-11-21');
        $endDate = Carbon::parse('2025-12-20');
        $workRecords = [];

        foreach ($staffList as $staff) {
            $currentDate = $startDate->copy();

            // 各日に対して作業実績を作成
            while ($currentDate->lte($endDate)) {
                // 一日あたり最低8時間（480分）の作業時間を確保
                $totalMinutes = 480;
                $remainingMinutes = $totalMinutes;

                // 作業開始可能時間：8時
                $dayStart = $currentDate->copy()->setTime(8, 0, 0);
                // 作業終了可能時間：22時
                $dayEnd = $currentDate->copy()->setTime(22, 0, 0);

                $currentTime = $dayStart->copy();
                $maxAttempts = 100; // 無限ループを防ぐための最大試行回数
                $attempts = 0;

                // 複数の作業セッションを作成（1つあたり60分〜240分）
                while ($remainingMinutes > 0 && $currentTime->lt($dayEnd) && $attempts < $maxAttempts) {
                    $attempts++;
                    
                    // 終了可能時間までの残り時間を計算
                    $availableMinutesUntilEnd = $currentTime->diffInMinutes($dayEnd);
                    
                    if ($availableMinutesUntilEnd <= 0) {
                        break;
                    }
                    
                    // 今回の作業時間を決定（最低60分、最高240分、残り時間と利用可能時間の小さい方）
                    $maxSessionMinutes = min(240, $remainingMinutes, $availableMinutesUntilEnd);
                    
                    if ($maxSessionMinutes < 60) {
                        // 残り時間が60分未満の場合は、全て使い切る
                        $sessionMinutes = $maxSessionMinutes;
                    } else {
                        $sessionMinutes = rand(60, $maxSessionMinutes);
                    }

                    if ($sessionMinutes <= 0) {
                        break;
                    }

                    // 図番と作業方法の組み合わせを試行して、有効な作業単価を見つける
                    $workRate = null;
                    $selectedDrawing = null;
                    $selectedWorkMethod = null;
                    $rateAttempts = 0;
                    
                    while (!$workRate && $rateAttempts < 50) {
                        $selectedDrawing = $drawings->random();
                        $selectedWorkMethod = $workMethods->random();
                        $workRate = $selectedDrawing->getEffectiveWorkRate($selectedWorkMethod->id, $currentTime);
                        $rateAttempts++;
                    }
                    
                    // 作業単価が見つからない場合は、残り時間を減らして次の時刻へ
                    if (!$workRate) {
                        // 時間を少し進めて次の試行へ（60分進める）
                        $currentTime->addMinutes(60);
                        // 残り時間も減らす（見つからなかった分）
                        $remainingMinutes = max(0, $remainingMinutes - 60);
                        continue;
                    }

                    $sessionEndTime = $currentTime->copy()->addMinutes($sessionMinutes);

                    // 良品数と不良数をランダムに生成
                    $quantityGood = rand(10, 100);
                    $quantityNg = rand(0, 10);

                    $workRecords[] = [
                        'drawing_id' => $selectedDrawing->id,
                        'work_method_id' => $selectedWorkMethod->id,
                        'staff_id' => $staff->id,
                        'work_rate_id' => $workRate->id,
                        'start_time' => $currentTime->format('Y-m-d H:i:s'),
                        'end_time' => $sessionEndTime->format('Y-m-d H:i:s'),
                        'work_minutes' => $sessionMinutes,
                        'quantity_good' => $quantityGood,
                        'quantity_ng' => $quantityNg,
                        'memo' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // 次の作業開始時刻を設定（少し間隔を空ける：5〜30分）
                    $breakMinutes = rand(5, 30);
                    $currentTime = $sessionEndTime->copy()->addMinutes($breakMinutes);
                    
                    // 残り時間を減らす
                    $remainingMinutes -= $sessionMinutes;
                }

                $currentDate->addDay();
            }
        }

        // データが存在する場合のみ挿入
        if (!empty($workRecords)) {
            // チャンクに分けて挿入（パフォーマンス向上）
            $chunks = array_chunk($workRecords, 500);
            foreach ($chunks as $chunk) {
                DB::table('work_records')->insert($chunk);
            }
            $this->command->info(count($workRecords) . '件の作業実績を作成しました。');
        }
    }
}

