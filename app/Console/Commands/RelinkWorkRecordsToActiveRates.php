<?php

namespace App\Console\Commands;

use App\Services\RelinkWorkRecordsService;
use Illuminate\Console\Command;

/**
 * 適用「しない」の作業単価に紐づいている作業実績を、有効な単価に再紐づけする
 */
class RelinkWorkRecordsToActiveRates extends Command
{
    protected $signature = 'work-records:relink-to-active-rates
                            {--dry-run : 実際には更新せず、対象件数のみ表示する}';

    protected $description = '適用フラグが無効(active_flg=false)の単価に紐づく作業実績を、有効な単価に再紐づけする';

    public function handle(RelinkWorkRecordsService $service): int
    {
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('【ドライラン】実際の更新は行いません。');
        }

        $result = $service->run($dryRun);

        foreach ($result['details'] as $line) {
            $this->line($line);
        }

        $this->newLine();
        $this->info('再紐づけ: ' . $result['updated'] . ' 件' . ($dryRun ? '（ドライランのため未更新）' : ''));
        if ($result['skipped'] > 0) {
            $this->info('スキップ: ' . $result['skipped'] . ' 件');
        }

        return self::SUCCESS;
    }
}
