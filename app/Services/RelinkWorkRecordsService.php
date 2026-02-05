<?php

namespace App\Services;

use App\Models\WorkRecord;
use App\Models\Drawing;

/**
 * 適用フラグが無効の単価に紐づく作業実績を、有効な単価に再紐づけする
 */
class RelinkWorkRecordsService
{
    /**
     * 再紐づけを実行する
     *
     * @param bool $dryRun true の場合は更新しない
     * @return array{ total: int, updated: int, skipped: int, details: array }
     */
    public function run(bool $dryRun = false): array
    {
        $workRecords = WorkRecord::whereHas('workRate', fn ($q) => $q->where('active_flg', false))
            ->with(['drawing', 'workRate', 'workMethod'])
            ->orderBy('id')
            ->get();

        $total = $workRecords->count();
        $updated = 0;
        $skipped = 0;
        $details = [];

        if ($total === 0) {
            return [
                'total' => 0,
                'updated' => 0,
                'skipped' => 0,
                'details' => ['再紐づけ対象の作業実績はありません。'],
            ];
        }

        foreach ($workRecords as $workRecord) {
            $drawing = $workRecord->drawing;
            if (!$drawing) {
                $details[] = "作業実績 ID:{$workRecord->id} — スキップ（図番が存在しません）";
                $skipped++;
                continue;
            }

            $effectiveRate = $drawing->getEffectiveWorkRate(
                $workRecord->work_method_id,
                $workRecord->start_time
            );

            if (!$effectiveRate) {
                $date = $workRecord->start_time?->format('Y-m-d') ?? '不明';
                $details[] = "作業実績 ID:{$workRecord->id} — スキップ（{$date} 時点で有効な単価がありません）";
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

            $toDate = $effectiveRate->effective_to?->format('Y-m-d') ?? '無期限';
            $details[] = sprintf(
                '作業実績 ID:%d → 単価 ID:%d（%s ～ %s）',
                $workRecord->id,
                $effectiveRate->id,
                $effectiveRate->effective_from?->format('Y-m-d') ?? '',
                $toDate
            );
            $updated++;
        }

        return [
            'total' => $total,
            'updated' => $updated,
            'skipped' => $skipped,
            'details' => $details,
        ];
    }
}
