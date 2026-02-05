<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkRateRequest;
use App\Http\Requests\UpdateWorkRateRequest;
use App\Models\WorkRate;
use App\Models\Drawing;
use App\Models\WorkMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WorkRateController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkRate::with(['drawing.client', 'workMethod'])
            ->withCount('workRecords')
            ->withCount(['workRecords as work_records_invoiced_count' => function ($q) {
                $q->whereHas('staffInvoiceDetails');
            }])
            ->orderBy('effective_from', 'desc')
            ->orderBy('drawing_id');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('drawing', function ($q) use ($search) {
                $q->where('drawing_number', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('drawing_id')) {
            $query->where('drawing_id', $request->drawing_id);
        }

        if ($request->filled('work_method_id')) {
            $query->where('work_method_id', $request->work_method_id);
        }

        $workRates = $query->paginate(20)->withQueryString();
        $drawings = Drawing::where('active_flag', true)->with('client')->orderBy('drawing_number')->get();
        $workMethods = WorkMethod::orderBy('name')->get();

        return Inertia::render('WorkRates/Index', [
            'workRates' => $workRates,
            'drawings' => $drawings,
            'workMethods' => $workMethods,
            'filters' => $request->only(['search', 'drawing_id', 'work_method_id']),
        ]);
    }

    public function create()
    {
        $drawings = Drawing::where('active_flag', true)->with('client')->orderBy('drawing_number')->get();
        $workMethods = WorkMethod::orderBy('name')->get();

        return Inertia::render('WorkRates/Create', [
            'drawings' => $drawings,
            'workMethods' => $workMethods,
        ]);
    }

    public function store(StoreWorkRateRequest $request)
    {
        DB::beginTransaction();
        try {
            // 既存の有効な単価を終了させる
            $existingRates = WorkRate::where('drawing_id', $request->drawing_id)
                ->where('work_method_id', $request->work_method_id)
                ->where(function ($query) use ($request) {
                    $query->whereNull('effective_to')
                        ->orWhere('effective_to', '>=', $request->effective_from);
                })
                ->get();

            foreach ($existingRates as $existingRate) {
                // 新しい単価の開始日より前で終了させる
                if ($existingRate->effective_from < $request->effective_from) {
                    $existingRate->update([
                        'effective_to' => date('Y-m-d', strtotime($request->effective_from . ' -1 day')),
                    ]);
                }
            }

            WorkRate::create($request->validated());

            DB::commit();

            return redirect()->route('work-rates.index')
                ->with('success', '作業単価を登録しました。');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors([
                'error' => '作業単価の登録に失敗しました: ' . $e->getMessage()
            ])->withInput();
        }
    }

    public function edit(WorkRate $workRate)
    {
        $workRate->load(['drawing.client', 'workMethod']);
        $drawings = Drawing::where('active_flag', true)->with('client')->orderBy('drawing_number')->get();
        $workMethods = WorkMethod::orderBy('name')->get();

        return Inertia::render('WorkRates/Edit', [
            'workRate' => $workRate,
            'drawings' => $drawings,
            'workMethods' => $workMethods,
        ]);
    }

    public function update(UpdateWorkRateRequest $request, WorkRate $workRate)
    {
        $workRate->update($request->validated());

        return redirect()->route('work-rates.index')
            ->with('success', '作業単価を更新しました。');
    }

    public function destroy(WorkRate $workRate)
    {
        $workRate->delete();

        return redirect()->route('work-rates.index')
            ->with('success', '作業単価を削除しました。');
    }
}

