<?php

namespace App\Console\Commands;

use App\Models\WorkRecord;
use App\Models\Drawing;
use Illuminate\Console\Command;

/**
 * 適用「しない」の作業単価に紐づいている作業実績を、有効な単価に再紐づけする
 */
class RelinkWorkRecordsToActiveRates extends Command
{
    protected $signature = 'work-records:relink-to-active-rates
                            {--dry-run : 実際には更新せず、対象件数のみ表示する}';

    protected $description = '適用フラグが無効(active_flg=false)の単価に紐づく作業実績を、有効な単価に再紐づけする';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('【ドライラン】実際の更新は行いません。');
        }

        // 適用「しない」の単価に紐づいている作業実績を取得
        $workRecords = WorkRecord::whereHas('workRate', fn ($q) => $q->where('active_flg', false))
            ->with(['drawing', 'workRate', 'workMethod'])
            ->orderBy('id')
            ->get();

        $total = $workRecords->count();
        if ($total === 0) {
            $this->info('再紐づけ対象の作業実績はありません。');
            return self::SUCCESS;
        }

        $this->info("対象: {$total} 件の作業実績");

        $updated = 0;
        $skipped = 0;

        foreach ($workRecords as $workRecord) {
            $drawing = $workRecord->drawing;
            if (!$drawing) {
                $this->line("  [スキップ] 作業実績 ID:{$workRecord->id} - 図番が存在しません");
                $skipped++;
                continue;
            }

            $effectiveRate = $drawing->getEffectiveWorkRate(
                $workRecord->work_method_id,
                $workRecord->start_time
            );

            if (!$effectiveRate) {
                $date = $workRecord->start_time?->format('Y-m-d') ?? '不明';
                $this->line("  [スキップ] 作業実績 ID:{$workRecord->id} - 日付 {$date} 時点で有効な単価がありません（図番:{$drawing->drawing_number} 作業方法:{$workRecord->workMethod?->name}）");
                $skipped++;
                continue;
            }

            if ((int) $effectiveRate->id === (int) $workRecord->work_rate_id) {
                $skipped++;
                continue;
            }

            if (!$dryRun) {
                $workRecord->update(['work_rate_id' => $effectiveRate->id]);
            }

            $this->line(sprintf(
                '  作業実績 ID:%d → 単価 ID:%d（%s ～ %s）',
                $workRecord->id,
                $effectiveRate->id,
                $effectiveRate->effective_from?->format('Y-m-d') ?? '',
                $effectiveRate->effective_to?->format('Y-m-d') ?? '無期限'
            ));
            $updated++;
        }

        $this->newLine();
        $this->info("再紐づけ: {$updated} 件" . ($dryRun ? '（ドライランのため未更新）' : ''));
        if ($skipped > 0) {
            $this->info("スキップ: {$skipped} 件");
        }

        return self::SUCCESS;
    }
}
