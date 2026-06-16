<?php

namespace App\Http\Controllers;

use App\Models\WorkRecord;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboard) {}

    /**
     * ダッシュボードを表示する。
     *
     * クエリ ?from=Y-m-d&to=Y-m-d で集計期間を指定。
     * 未指定の場合は当月（Asia/Tokyo）を既定とする。
     */
    public function index(Request $request)
    {
        $tz = 'Asia/Tokyo';

        // 既定の集計期間：今日を含む締めサイクル（21日〜翌月20日）
        $now = Carbon::now($tz);
        if ($now->day >= 21) {
            // 当月21日 〜 翌月20日
            $defaultFrom = $now->copy()->day(21)->startOfDay();
            $defaultTo = $now->copy()->addMonthNoOverflow()->day(20)->endOfDay();
        } else {
            // 前月21日 〜 当月20日
            $defaultFrom = $now->copy()->subMonthNoOverflow()->day(21)->startOfDay();
            $defaultTo = $now->copy()->day(20)->endOfDay();
        }

        $from = $this->parseDate($request->query('from')) ?? $defaultFrom;
        $to = $this->parseDate($request->query('to')) ?? $defaultTo;

        // from > to の場合は入れ替え
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $fromStr = $from->format('Y-m-d');
        $toStr = $to->format('Y-m-d');

        return Inertia::render('Dashboard', [
            'summary' => $this->dashboard->summary($fromStr, $toStr),
            'rateNegotiation' => $this->dashboard->rateNegotiation($fromStr, $toStr),
            'staffProductivity' => $this->dashboard->staffProductivity($fromStr, $toStr),
            'monthlyTrend' => $this->dashboard->monthlyTrend(6),
            'filters' => [
                'from' => $fromStr,
                'to' => $toStr,
            ],
        ]);
    }

    /**
     * 採算分析の行（図番×作業方法）に紐づく作業実績明細を返す（モーダル用 JSON）。
     *
     * クエリ: drawing_id, work_method_id, from, to
     */
    public function workRecords(Request $request)
    {
        $validated = $request->validate([
            'drawing_id' => ['required', 'integer'],
            'work_method_id' => ['required', 'integer'],
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d'],
        ]);

        $records = WorkRecord::query()
            ->where('drawing_id', $validated['drawing_id'])
            ->where('work_method_id', $validated['work_method_id'])
            ->whereBetween(\DB::raw('DATE(start_time)'), [$validated['from'], $validated['to']])
            ->with(['staff', 'drawing', 'workRate'])
            ->orderBy('start_time')
            ->get();

        $rows = $records->map(function (WorkRecord $r) {
            $quantity = $r->quantity_good + $r->quantity_ng;
            $weightPerUnit = (float) ($r->drawing->weight_per_unit ?? 0);
            $weight = $quantity * $weightPerUnit;

            // 代表単価（正社員→個人事業主→残業）。想定金額は採算分析の revenue と同基準。
            $rate = null;
            if ($r->workRate) {
                $rate = $r->workRate->rate_employee
                    ?? $r->workRate->rate_contractor
                    ?? $r->workRate->rate_overtime;
            }
            $rate = $rate !== null ? (float) $rate : 0.0;
            $amount = $quantity * round($weightPerUnit * $rate);

            return [
                'id' => $r->id,
                'staff_name' => $r->staff->name ?? '—',
                'date' => $r->start_time?->format('Y/m/d'),
                'start' => $r->start_time?->format('H:i'),
                'end' => $r->end_time?->format('H:i'),
                'work_minutes' => (int) $r->work_minutes,
                'quantity_good' => (int) $r->quantity_good,
                'quantity_ng' => (int) $r->quantity_ng,
                'total_quantity' => $quantity,
                'weight' => round($weight, 1),
                'amount' => round($amount),
                'memo' => $r->memo,
                'is_invoiced' => $r->isInvoiced(),
            ];
        });

        return response()->json([
            'records' => $rows,
        ]);
    }

    /**
     * Y-m-d 文字列を Carbon に変換（不正値は null）。
     */
    private function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value, 'Asia/Tokyo')->startOfDay();
        } catch (\Exception $e) {
            return null;
        }
    }
}
