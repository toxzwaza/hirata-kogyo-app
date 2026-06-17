<?php

namespace App\Services;

use App\Models\ClientInvoice;
use App\Models\Drawing;
use App\Models\StaffInvoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * ダッシュボードサービス
 *
 * 作業実績・請求書からダッシュボード表示用の集計値を算出する。
 * 集計は SQL 集約（selectRaw + groupBy）で行い、全レコードのロードを避ける。
 *
 * 金額（想定売上）の基準は StaffInvoiceService の計算式に合わせ、
 *   個単価 = round(weight_per_unit × kg単価)
 *   金額   = 数量 × 個単価
 * とする。kg単価は図番ごとの「代表単価」＝ rate_employee → rate_contractor →
 * rate_overtime のフォールバック（COALESCE）を用いる。
 * これにより、正社員単価が未設定（個人事業主専用）の図番が ¥0 扱いになるのを防ぐ。
 * ※ あくまで作業実績からの「想定値」であり、確定済請求書の金額とは独立した参考指標。
 */
class DashboardService
{
    /** 図番の代表単価（円/kg）：正社員→個人事業主→残業 のフォールバック */
    private const REP_RATE = 'COALESCE(r.rate_employee, r.rate_contractor, r.rate_overtime, 0)';

    /**
     * サマリKPI：今期間の請求予定額。
     *
     * @param  string  $from  Y-m-d
     * @param  string  $to  Y-m-d
     * @return array{client_invoice_total: float, staff_invoice_total: float}
     */
    public function summary(string $from, string $to): array
    {
        // 期間に重なる（period_from <= to かつ period_to >= from）draft/fixed の請求書を集計
        $clientTotal = ClientInvoice::whereIn('status', ['draft', 'fixed'])
            ->where('period_from', '<=', $to)
            ->where('period_to', '>=', $from)
            ->sum('total');

        $staffTotal = StaffInvoice::whereIn('status', ['draft', 'fixed'])
            ->where('period_from', '<=', $to)
            ->where('period_to', '>=', $from)
            ->sum('total');

        return [
            'client_invoice_total' => (float) $clientTotal,
            'staff_invoice_total' => (float) $staffTotal,
        ];
    }

    /**
     * 単価交渉支援：図番×作業方法ごとの採算分析（実効時給 昇順）。
     *
     * 各行に以下を含む：
     *  - 件数 / 総作業時間(分) / 総数量 / 総重量(kg) / 想定売上(円)
     *  - 生産性 kg/h
     *  - 実効時給（想定売上 ÷ 時間）
     *  - 現行単価（今日時点で有効な代表単価）
     * 必要単価・値上げ要求額は目標時給に依存するためフロント側で算出する。
     *
     * @param  string  $from  Y-m-d
     * @param  string  $to  Y-m-d
     * @return array<int, array<string, mixed>>
     */
    public function rateNegotiation(string $from, string $to): array
    {
        $rows = DB::table('work_records as wr')
            ->join('drawings as d', 'wr.drawing_id', '=', 'd.id')
            ->join('work_methods as wm', 'wr.work_method_id', '=', 'wm.id')
            ->join('clients as c', 'd.client_id', '=', 'c.id')
            ->join('work_rates as r', 'wr.work_rate_id', '=', 'r.id')
            ->whereBetween(DB::raw('DATE(wr.start_time)'), [$from, $to])
            ->groupBy('wr.drawing_id', 'wr.work_method_id')
            ->selectRaw('wr.drawing_id as drawing_id')
            ->selectRaw('wr.work_method_id as work_method_id')
            ->selectRaw('MAX(d.drawing_number) as drawing_number')
            ->selectRaw('MAX(d.product_name) as product_name')
            ->selectRaw('MAX(c.name) as client_name')
            ->selectRaw('MAX(wm.name) as work_method_name')
            ->selectRaw('MAX(d.weight_per_unit) as weight_per_unit')
            ->selectRaw('COUNT(*) as record_count')
            ->selectRaw('SUM(wr.work_minutes) as total_minutes')
            ->selectRaw('SUM(wr.quantity_good + wr.quantity_ng) as total_quantity')
            ->selectRaw('SUM((wr.quantity_good + wr.quantity_ng) * d.weight_per_unit) as total_weight')
            ->selectRaw('SUM((wr.quantity_good + wr.quantity_ng) * ROUND(d.weight_per_unit * '.self::REP_RATE.')) as revenue')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $minutes = (int) $row->total_minutes;
            if ($minutes <= 0) {
                // 作業時間ゼロは時給・生産性を算出できないためスキップ
                continue;
            }
            $hours = $minutes / 60;
            $weight = (float) $row->total_weight;
            $revenue = (float) $row->revenue;

            // 今日時点で有効な単価（代表単価：正社員→個人事業主→残業）
            $drawing = Drawing::find($row->drawing_id);
            $effectiveRate = $drawing
                ? $drawing->getEffectiveWorkRate((int) $row->work_method_id)
                : null;
            $currentRate = null;
            if ($effectiveRate) {
                $rep = $effectiveRate->rate_employee
                    ?? $effectiveRate->rate_contractor
                    ?? $effectiveRate->rate_overtime;
                $currentRate = $rep !== null ? (float) $rep : null;
            }

            $result[] = [
                'drawing_id' => (int) $row->drawing_id,
                'work_method_id' => (int) $row->work_method_id,
                'drawing_number' => $row->drawing_number,
                'product_name' => $row->product_name,
                'client_name' => $row->client_name,
                'work_method_name' => $row->work_method_name,
                'weight_per_unit' => (float) $row->weight_per_unit,
                'record_count' => (int) $row->record_count,
                'total_minutes' => $minutes,
                'total_quantity' => (int) $row->total_quantity,
                'total_weight' => round($weight, 1),
                'revenue' => round($revenue),
                'kg_per_hour' => round($weight / $hours, 2),
                'effective_hourly' => round($revenue / $hours),
                'current_rate' => $currentRate,
            ];
        }

        // 実効時給 昇順（割に合わない図番を上位に）
        usort($result, fn ($a, $b) => $a['effective_hourly'] <=> $b['effective_hourly']);

        return $result;
    }

    /**
     * スタッフ別生産性：期間内のスタッフごとの生産量・実効時給。
     *
     * @param  string  $from  Y-m-d
     * @param  string  $to  Y-m-d
     * @return array<int, array<string, mixed>>
     */
    public function staffProductivity(string $from, string $to): array
    {
        $rows = DB::table('work_records as wr')
            ->join('drawings as d', 'wr.drawing_id', '=', 'd.id')
            ->join('work_rates as r', 'wr.work_rate_id', '=', 'r.id')
            ->join('staff as s', 'wr.staff_id', '=', 's.id')
            ->whereBetween(DB::raw('DATE(wr.start_time)'), [$from, $to])
            ->groupBy('wr.staff_id')
            ->selectRaw('wr.staff_id as staff_id')
            ->selectRaw('MAX(s.name) as staff_name')
            ->selectRaw('COUNT(*) as record_count')
            ->selectRaw('SUM(wr.work_minutes) as total_minutes')
            ->selectRaw('SUM(wr.quantity_good + wr.quantity_ng) as total_quantity')
            ->selectRaw('SUM((wr.quantity_good + wr.quantity_ng) * d.weight_per_unit) as total_weight')
            ->selectRaw('SUM((wr.quantity_good + wr.quantity_ng) * ROUND(d.weight_per_unit * COALESCE(r.rate_employee, r.rate_contractor, r.rate_overtime, 0))) as revenue')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $minutes = (int) $row->total_minutes;
            $hours = $minutes > 0 ? $minutes / 60 : null;
            $weight = (float) $row->total_weight;
            $revenue = (float) $row->revenue;

            $result[] = [
                'staff_id' => (int) $row->staff_id,
                'staff_name' => $row->staff_name,
                'record_count' => (int) $row->record_count,
                'total_quantity' => (int) $row->total_quantity,
                'total_weight' => round($weight, 1),
                'revenue' => round($revenue),
                'kg_per_hour' => $hours ? round($weight / $hours, 2) : null,
                'effective_hourly' => $hours ? round($revenue / $hours) : null,
            ];
        }

        // 生産重量 降順
        usort($result, fn ($a, $b) => $b['total_weight'] <=> $a['total_weight']);

        return $result;
    }

    /**
     * 月次推移：直近 N ヶ月の想定売上・生産重量。
     *
     * @return array<int, array{month: string, revenue: float, total_weight: float}>
     */
    public function monthlyTrend(int $months = 6): array
    {
        $start = Carbon::now('Asia/Tokyo')->startOfMonth()->subMonths($months - 1);

        $rows = DB::table('work_records as wr')
            ->join('drawings as d', 'wr.drawing_id', '=', 'd.id')
            ->join('work_rates as r', 'wr.work_rate_id', '=', 'r.id')
            ->where('wr.start_time', '>=', $start->format('Y-m-d 00:00:00'))
            ->groupBy(DB::raw("DATE_FORMAT(wr.start_time, '%Y-%m')"))
            ->selectRaw("DATE_FORMAT(wr.start_time, '%Y-%m') as month")
            ->selectRaw('SUM((wr.quantity_good + wr.quantity_ng) * d.weight_per_unit) as total_weight')
            ->selectRaw('SUM((wr.quantity_good + wr.quantity_ng) * ROUND(d.weight_per_unit * COALESCE(r.rate_employee, r.rate_contractor, r.rate_overtime, 0))) as revenue')
            ->get()
            ->keyBy('month');

        // 月の連番を作り、データが無い月は 0 で埋める
        $result = [];
        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonths($i)->format('Y-m');
            $row = $rows->get($month);
            $result[] = [
                'month' => $month,
                'revenue' => $row ? round((float) $row->revenue) : 0,
                'total_weight' => $row ? round((float) $row->total_weight, 1) : 0,
            ];
        }

        return $result;
    }
}
