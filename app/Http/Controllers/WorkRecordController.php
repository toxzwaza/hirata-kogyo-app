<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkRecordRequest;
use App\Http\Requests\UpdateWorkRecordRequest;
use App\Models\WorkRecord;
use App\Models\Drawing;
use App\Models\WorkMethod;
use App\Models\Staff;
use App\Models\DefectType;
use App\Models\WorkRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

/**
 * 作業実績管理コントローラー
 */
class WorkRecordController extends Controller
{
    /**
     * 作業実績一覧
     */
    public function index(Request $request)
    {
        $query = WorkRecord::with([
            'drawing.client',
            'workMethod',
            'staff.staffType',
            'workRate',
            'defects.defectType'
        ])
            ->orderBy('start_time', 'desc');

        // フィルタリング
        if ($request->filled('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }
        if ($request->filled('drawing_id')) {
            $query->where('drawing_id', $request->drawing_id);
        }
        if ($request->filled('work_method_id')) {
            $query->where('work_method_id', $request->work_method_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('start_time', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('start_time', '<=', $request->date_to);
        }

        $workRecords = $query->paginate(20)->withQueryString();

        // マスタデータ
        $staffList = Staff::where('active_flag', true)
            ->with('staffType')
            ->orderBy('name')
            ->get();
        $drawings = Drawing::where('active_flag', true)
            ->with('client')
            ->orderBy('drawing_number')
            ->get();
        $workMethods = WorkMethod::orderBy('name')->get();

        return Inertia::render('WorkRecords/Index', [
            'workRecords' => $workRecords,
            'staffList' => $staffList,
            'drawings' => $drawings,
            'workMethods' => $workMethods,
            'filters' => $request->only(['staff_id', 'drawing_id', 'work_method_id', 'date_from', 'date_to']),
        ]);
    }

    /**
     * 作業実績登録画面
     */
    public function create()
    {
        $staffList = Staff::where('active_flag', true)
            ->with('staffType')
            ->orderBy('name')
            ->get();
        $drawings = Drawing::where('active_flag', true)
            ->with(['client', 'workRates' => function($query) {
                $query->orderBy('effective_from', 'desc');
            }, 'workRates.workMethod'])
            ->orderBy('drawing_number')
            ->get();
        $workMethods = WorkMethod::orderBy('name')->get();
        $defectTypes = DefectType::orderBy('name')->get();

        return Inertia::render('WorkRecords/Create', [
            'staffList' => $staffList,
            'drawings' => $drawings,
            'workMethods' => $workMethods,
            'defectTypes' => $defectTypes,
        ]);
    }

    /**
     * 作業実績登録画面（スマホ用）
     */
    public function createForMobile(Request $request)
    {
        // staff_idパラメータが指定されている場合、そのIDでログイン処理を行う
        if ($request->has('staff_id') && !auth()->check()) {
            $staffId = $request->input('staff_id');
            $staff = Staff::where('id', $staffId)
                ->where('active_flag', true)
                ->first();
            
            if ($staff) {
                // パスワードチェックなしでログイン（危険だが、とりあえず）
                Auth::guard('web')->login($staff);
                $request->session()->regenerate();
            }
        }
        
        // ログイン中のスタッフを取得
        // 注意: auth()->id()はlogin_idを返すため、auth()->user()->idを使用
        $currentStaff = auth()->user();
        
        // ログインしていない場合はエラー
        if (!$currentStaff) {
            return redirect()->route('login')
                ->with('error', 'ログインが必要です。');
        }
        
        // スタッフ情報を取得（スタッフタイプも含める）
        $currentStaff = Staff::with('staffType')->findOrFail($currentStaff->id);
        
        $drawings = Drawing::where('active_flag', true)
            ->with(['client', 'workRates' => function($query) {
                $query->orderBy('effective_from', 'desc');
            }, 'workRates.workMethod'])
            ->orderBy('drawing_number')
            ->get();
        
        $workMethods = WorkMethod::orderBy('name')->get();
        $defectTypes = DefectType::orderBy('name')->get();

        return Inertia::render('Mobile/WorkRecords/Create', [
            'currentStaff' => $currentStaff,
            'drawings' => $drawings,
            'workMethods' => $workMethods,
            'defectTypes' => $defectTypes,
        ]);
    }

    /**
     * 作業実績登録処理（スマホ用）
     */
    public function storeForMobile(StoreWorkRecordRequest $request)
    {
        // ログイン中のスタッフIDを使用（auth()->id()はlogin_idを返すため、user()->idを使用）
        $request->merge(['staff_id' => auth()->user()->id]);
        
        DB::beginTransaction();
        try {
            // 作業時間を計算（分）
            $startTime = Carbon::parse($request->start_time);
            $endTime = Carbon::parse($request->end_time);
            $workMinutes = $startTime->diffInMinutes($endTime);

            // 有効な作業単価を取得
            $drawing = Drawing::findOrFail($request->drawing_id);
            $workRate = $drawing->getEffectiveWorkRate(
                $request->work_method_id,
                $startTime
            );

            if (!$workRate) {
                return back()->withErrors([
                    'work_method_id' => '指定された日時点で有効な作業単価が設定されていません。'
                ]);
            }

            // 作業実績を作成
            $workRecord = WorkRecord::create([
                'drawing_id' => $request->drawing_id,
                'work_method_id' => $request->work_method_id,
                'staff_id' => $request->staff_id,
                'work_rate_id' => $workRate->id,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'work_minutes' => $workMinutes,
                'quantity_good' => $request->quantity_good,
                'quantity_ng' => $request->quantity_ng,
                'memo' => $request->memo,
            ]);

            // 不良内訳を登録
            if ($request->filled('defects') && is_array($request->defects)) {
                // 不良数の合計が一致するかチェック
                $totalDefectQuantity = array_sum(array_column($request->defects, 'defect_quantity'));
                if ($totalDefectQuantity !== $request->quantity_ng) {
                    throw new \Exception('不良内訳の合計が不良数と一致しません。');
                }

                foreach ($request->defects as $defect) {
                    $workRecord->defects()->create([
                        'defect_type_id' => $defect['defect_type_id'],
                        'defect_quantity' => $defect['defect_quantity'],
                    ]);
                }
            }

            DB::commit();

            // スマホ版では同じ画面に戻り、成功メッセージを表示
            return redirect()->route('mobile.work-records.create')
                ->with('success', '作業実績を登録しました。');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors([
                'error' => '作業実績の登録に失敗しました: ' . $e->getMessage()
            ])->withInput();
        }
    }

    /**
     * 作業実績登録処理
     */
    public function store(StoreWorkRecordRequest $request)
    {
        DB::beginTransaction();
        try {
            // 作業時間を計算（分）
            $startTime = Carbon::parse($request->start_time);
            $endTime = Carbon::parse($request->end_time);
            $workMinutes = $startTime->diffInMinutes($endTime);

            // 有効な作業単価を取得
            $drawing = Drawing::findOrFail($request->drawing_id);
            $workRate = $drawing->getEffectiveWorkRate(
                $request->work_method_id,
                $startTime
            );

            if (!$workRate) {
                return back()->withErrors([
                    'work_method_id' => '指定された日時点で有効な作業単価が設定されていません。'
                ]);
            }

            // 作業実績を作成
            $workRecord = WorkRecord::create([
                'drawing_id' => $request->drawing_id,
                'work_method_id' => $request->work_method_id,
                'staff_id' => $request->staff_id,
                'work_rate_id' => $workRate->id,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'work_minutes' => $workMinutes,
                'quantity_good' => $request->quantity_good,
                'quantity_ng' => $request->quantity_ng,
                'memo' => $request->memo,
            ]);

            // 不良内訳を登録
            if ($request->filled('defects') && is_array($request->defects)) {
                // 不良数の合計が一致するかチェック
                $totalDefectQuantity = array_sum(array_column($request->defects, 'defect_quantity'));
                if ($totalDefectQuantity !== $request->quantity_ng) {
                    throw new \Exception('不良内訳の合計が不良数と一致しません。');
                }

                foreach ($request->defects as $defect) {
                    $workRecord->defects()->create([
                        'defect_type_id' => $defect['defect_type_id'],
                        'defect_quantity' => $defect['defect_quantity'],
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('work-records.index')
                ->with('success', '作業実績を登録しました。');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors([
                'error' => '作業実績の登録に失敗しました: ' . $e->getMessage()
            ])->withInput();
        }
    }

    /**
     * 作業実績詳細・編集画面
     */
    public function edit(WorkRecord $workRecord)
    {
        // 請求書に含まれている場合は編集不可
        if ($workRecord->isInvoiced()) {
            return redirect()->route('work-records.index')
                ->with('error', 'この作業実績は請求書に含まれているため編集できません。');
        }

        $workRecord->load([
            'drawing.client',
            'workMethod',
            'staff.staffType',
            'workRate',
            'defects.defectType'
        ]);

        $staffList = Staff::where('active_flag', true)
            ->with('staffType')
            ->orderBy('name')
            ->get();
        $drawings = Drawing::where('active_flag', true)
            ->with(['client', 'workRates' => function($query) {
                $query->orderBy('effective_from', 'desc');
            }, 'workRates.workMethod'])
            ->orderBy('drawing_number')
            ->get();
        $workMethods = WorkMethod::orderBy('name')->get();
        $defectTypes = DefectType::orderBy('name')->get();

        return Inertia::render('WorkRecords/Edit', [
            'workRecord' => $workRecord,
            'staffList' => $staffList,
            'drawings' => $drawings,
            'workMethods' => $workMethods,
            'defectTypes' => $defectTypes,
        ]);
    }

    /**
     * 作業実績更新処理
     */
    public function update(UpdateWorkRecordRequest $request, WorkRecord $workRecord)
    {
        // 請求書に含まれている場合は編集不可
        if ($workRecord->isInvoiced()) {
            return back()->withErrors([
                'error' => 'この作業実績は請求書に含まれているため編集できません。'
            ]);
        }

        DB::beginTransaction();
        try {
            // 作業時間を計算（分）
            $startTime = Carbon::parse($request->start_time);
            $endTime = Carbon::parse($request->end_time);
            $workMinutes = $startTime->diffInMinutes($endTime);

            // 有効な作業単価を取得（変更されている可能性があるため再取得）
            $drawing = Drawing::findOrFail($request->drawing_id);
            $workRate = $drawing->getEffectiveWorkRate(
                $request->work_method_id,
                $startTime
            );

            if (!$workRate) {
                return back()->withErrors([
                    'work_method_id' => '指定された日時点で有効な作業単価が設定されていません。'
                ]);
            }

            // 作業実績を更新
            $workRecord->update([
                'drawing_id' => $request->drawing_id,
                'work_method_id' => $request->work_method_id,
                'staff_id' => $request->staff_id,
                'work_rate_id' => $workRate->id,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'work_minutes' => $workMinutes,
                'quantity_good' => $request->quantity_good,
                'quantity_ng' => $request->quantity_ng,
                'memo' => $request->memo,
            ]);

            // 既存の不良内訳を削除
            $workRecord->defects()->delete();

            // 不良内訳を再登録
            if ($request->filled('defects') && is_array($request->defects)) {
                // 不良数の合計が一致するかチェック
                $totalDefectQuantity = array_sum(array_column($request->defects, 'defect_quantity'));
                if ($totalDefectQuantity !== $request->quantity_ng) {
                    throw new \Exception('不良内訳の合計が不良数と一致しません。');
                }

                foreach ($request->defects as $defect) {
                    $workRecord->defects()->create([
                        'defect_type_id' => $defect['defect_type_id'],
                        'defect_quantity' => $defect['defect_quantity'],
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('work-records.index')
                ->with('success', '作業実績を更新しました。');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors([
                'error' => '作業実績の更新に失敗しました: ' . $e->getMessage()
            ])->withInput();
        }
    }

    /**
     * 作業実績削除処理
     */
    public function destroy(WorkRecord $workRecord)
    {
        // 請求書に含まれている場合は削除不可
        if ($workRecord->isInvoiced()) {
            return back()->withErrors([
                'error' => 'この作業実績は請求書に含まれているため削除できません。'
            ]);
        }

        DB::beginTransaction();
        try {
            // 不良内訳を削除
            $workRecord->defects()->delete();
            // 作業実績を削除
            $workRecord->delete();

            DB::commit();

            return redirect()->route('work-records.index')
                ->with('success', '作業実績を削除しました。');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors([
                'error' => '作業実績の削除に失敗しました: ' . $e->getMessage()
            ]);
        }
    }
}


