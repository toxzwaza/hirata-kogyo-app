<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Drawing;
use App\Models\WorkMethod;
use App\Models\WorkRate;
use App\Models\WorkRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * 手動登録（未登録図番）の作業実績を、図番・作業単価へ紐づける管理機能。
 *
 * スタッフがモバイルで手動入力した未登録図番の実績（drawing_id が null）を、
 * 管理者がチェックボックスで手動グルーピングし、まとめて
 *  - 既存図番へ紐づける（linkExisting）
 *  - 新規図番＋単価を登録して紐づける（createAndLink）
 * いずれかで処理する。紐づけにより drawing_id / work_rate_id が確定し、請求対象になる。
 */
class ManualWorkRecordController extends Controller
{
    /**
     * 紐づけ画面。未紐づけ（drawing_id が null）の手動レコード一覧を表示する。
     */
    public function index()
    {
        $records = WorkRecord::whereNull('drawing_id')
            ->with(['workMethod', 'staff'])
            ->orderBy('manual_drawing_number')
            ->orderBy('start_time')
            ->get()
            ->map(function (WorkRecord $r) {
                return [
                    'id' => $r->id,
                    'manual_drawing_number' => $r->manual_drawing_number,
                    'manual_product_name' => $r->manual_product_name,
                    'manual_client_name' => $r->manual_client_name,
                    'work_method_id' => $r->work_method_id,
                    'work_method_name' => $r->workMethod->name ?? '',
                    'staff_name' => $r->staff->name ?? '',
                    'start_time' => $r->start_time?->format('Y-m-d H:i'),
                    'quantity_good' => $r->quantity_good,
                ];
            });

        $drawings = Drawing::with('client')
            ->orderBy('drawing_number')
            ->get(['id', 'drawing_number', 'product_name', 'client_id', 'weight_per_unit']);
        $workMethods = WorkMethod::orderBy('name')->get(['id', 'name']);
        $clients = Client::orderBy('name')->get(['id', 'name']);

        return Inertia::render('WorkRecords/Manual/Index', [
            'records' => $records,
            'drawings' => $drawings,
            'workMethods' => $workMethods,
            'clients' => $clients,
        ]);
    }

    /**
     * 選択した手動レコードを既存図番へ一括紐づけする。
     * 各レコードの作業方法・作業日時点で有効な単価を解決して work_rate_id を設定する。
     */
    public function linkExisting(Request $request)
    {
        $validated = $request->validate([
            'work_record_ids' => ['required', 'array', 'min:1'],
            'work_record_ids.*' => ['integer', 'exists:work_records,id'],
            'drawing_id' => ['required', 'exists:drawings,id'],
        ], [], [
            'work_record_ids' => '対象レコード',
            'drawing_id' => '図番',
        ]);

        $drawing = Drawing::findOrFail($validated['drawing_id']);
        $records = WorkRecord::whereIn('id', $validated['work_record_ids'])
            ->whereNull('drawing_id')
            ->with('workMethod')
            ->get();

        if ($records->isEmpty()) {
            return back()->withErrors(['error' => '対象の未処理レコードが見つかりませんでした。']);
        }

        // 事前チェック：選択レコードの各作業方法に有効な単価があるか
        $missingMethods = [];
        foreach ($records as $r) {
            $rate = $drawing->getEffectiveWorkRate($r->work_method_id, $r->start_time);
            if (! $rate) {
                $missingMethods[$r->workMethod->name ?? ('方法ID:' . $r->work_method_id)] = true;
            }
        }
        if (! empty($missingMethods)) {
            $methods = implode('・', array_keys($missingMethods));
            return back()->withErrors([
                'error' => "図番「{$drawing->drawing_number}」には作業方法【{$methods}】の有効な作業単価がありません。先に作業単価マスタで単価を登録してください。",
            ]);
        }

        DB::beginTransaction();
        try {
            $linked = 0;
            foreach ($records as $r) {
                $rate = $drawing->getEffectiveWorkRate($r->work_method_id, $r->start_time);
                $r->update([
                    'drawing_id' => $drawing->id,
                    'work_rate_id' => $rate->id,
                ]);
                $linked++;
            }
            DB::commit();

            return redirect()->route('work-records.manual.index')
                ->with('success', "{$linked}件を図番「{$drawing->drawing_number}」に紐づけました。");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => '紐づけに失敗しました: ' . $e->getMessage()]);
        }
    }

    /**
     * 新規図番＋作業単価を登録し、選択した手動レコードを紐づける。
     * 作業方法ごとに単価を登録し、各レコードの方法・日時に対応する単価を解決する。
     */
    public function createAndLink(Request $request)
    {
        $validated = $request->validate([
            'work_record_ids' => ['required', 'array', 'min:1'],
            'work_record_ids.*' => ['integer', 'exists:work_records,id'],
            'drawing_number' => ['required', 'string', 'max:255', 'unique:drawings,drawing_number'],
            'product_name' => ['required', 'string', 'max:255'],
            'client_id' => ['required', 'exists:clients,id'],
            'weight_per_unit' => ['required', 'numeric', 'min:0'],
            'effective_from' => ['required', 'date'],
            'rates' => ['required', 'array', 'min:1'],
            'rates.*.work_method_id' => ['required', 'exists:work_methods,id'],
            'rates.*.rate_employee' => ['required', 'numeric', 'min:0'],
            'rates.*.rate_contractor' => ['required', 'numeric', 'min:0'],
            'rates.*.rate_overtime' => ['nullable', 'numeric', 'min:0'],
        ], [], [
            'drawing_number' => '品番',
            'product_name' => '品名',
            'client_id' => '得意先',
            'weight_per_unit' => '1個あたり重量',
            'effective_from' => '適用開始日',
        ]);

        $records = WorkRecord::whereIn('id', $validated['work_record_ids'])
            ->whereNull('drawing_id')
            ->get();

        if ($records->isEmpty()) {
            return back()->withErrors(['error' => '対象の未処理レコードが見つかりませんでした。']);
        }

        DB::beginTransaction();
        try {
            $drawing = Drawing::create([
                'client_id' => $validated['client_id'],
                'drawing_number' => $validated['drawing_number'],
                'product_name' => $validated['product_name'],
                'weight_per_unit' => $validated['weight_per_unit'],
                'active_flag' => true,
            ]);

            foreach ($validated['rates'] as $rate) {
                WorkRate::create([
                    'drawing_id' => $drawing->id,
                    'work_method_id' => $rate['work_method_id'],
                    'rate_employee' => $rate['rate_employee'],
                    'rate_contractor' => $rate['rate_contractor'],
                    'rate_overtime' => $rate['rate_overtime'] ?? null,
                    'effective_from' => $validated['effective_from'],
                    'effective_to' => null,
                    'active_flg' => true,
                ]);
            }

            $drawing->refresh();

            $linked = 0;
            $noRate = 0;
            foreach ($records as $r) {
                $rate = $drawing->getEffectiveWorkRate($r->work_method_id, $r->start_time);
                $r->update([
                    'drawing_id' => $drawing->id,
                    'work_rate_id' => $rate?->id,
                ]);
                $linked++;
                if (! $rate) {
                    $noRate++;
                }
            }

            DB::commit();

            $msg = "図番「{$drawing->drawing_number}」を登録し、{$linked}件を紐づけました。";
            if ($noRate > 0) {
                $msg .= "（うち{$noRate}件は作業方法に対応する単価または適用日が合わず未確定です。作業単価マスタで単価を追加してください）";
            }

            return redirect()->route('work-records.manual.index')->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => '登録に失敗しました: ' . $e->getMessage()]);
        }
    }
}
