<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PreservesListFilters;
use App\Http\Requests\StoreDrawingRequest;
use App\Http\Requests\UpdateDrawingRequest;
use App\Models\Drawing;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DrawingController extends Controller
{
    use PreservesListFilters;

    public function index(Request $request)
    {
        $today = now()->format('Y-m-d');

        // 現在有効な作業単価（適用期間内・適用フラグON）を作業方法ごとに読み込む
        $query = Drawing::with([
            'client',
            'workRates' => function ($q) use ($today) {
                $q->where('active_flg', true)
                    ->where('effective_from', '<=', $today)
                    ->where(function ($q2) use ($today) {
                        $q2->whereNull('effective_to')
                            ->orWhere('effective_to', '>=', $today);
                    })
                    ->with('workMethod')
                    ->orderBy('work_method_id')
                    ->orderBy('effective_from', 'desc');
            },
        ])->orderBy('drawing_number');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('drawing_number', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->has('active_flag') && $request->active_flag !== null && $request->active_flag !== '') {
            $query->where('active_flag', filter_var($request->active_flag, FILTER_VALIDATE_BOOLEAN));
        }

        $drawings = $query->paginate(20)->withQueryString();

        // 各図番に現行の客先単価（kg単価・個単価）を付与する
        // 個単価(円/個) = round(1個あたり重量(kg/個) × kg単価(円/kg))
        $drawings->getCollection()->transform(function ($drawing) {
            $weight = (float) $drawing->weight_per_unit;
            $seenMethods = [];
            $rates = [];

            foreach ($drawing->workRates as $workRate) {
                // 作業方法ごとに最新の有効単価（effective_from 降順の先頭）のみ採用
                if (in_array($workRate->work_method_id, $seenMethods, true)) {
                    continue;
                }
                $seenMethods[] = $workRate->work_method_id;

                // 客先単価が未設定の場合は表示対象外
                if ($workRate->rate_employee === null) {
                    continue;
                }

                $kgRate = (float) $workRate->rate_employee;
                $rates[] = [
                    'work_method_name' => $workRate->workMethod->name ?? '',
                    'kg_rate' => $kgRate,
                    'unit_price' => round($weight * $kgRate),
                ];
            }

            $drawing->setAttribute('effective_rates', $rates);
            $drawing->unsetRelation('workRates');

            return $drawing;
        });

        $clients = Client::orderBy('name')->get();

        return Inertia::render('Drawings/Index', [
            'drawings' => $drawings,
            'clients' => $clients,
            'filters' => $request->only(['search', 'client_id', 'active_flag']),
        ]);
    }

    public function create()
    {
        $clients = Client::orderBy('name')->get();

        return Inertia::render('Drawings/Create', [
            'clients' => $clients,
        ]);
    }

    public function store(StoreDrawingRequest $request)
    {
        $data = $request->validated();
        $data['active_flag'] = $request->has('active_flag') ? $request->active_flag : true;

        // 画像アップロード
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('drawings', 'public');
            $data['image_path'] = $path;
        }

        Drawing::create($data);

        return redirect()->route('drawings.index')
            ->with('success', '図番を登録しました。');
    }

    public function edit(Drawing $drawing)
    {
        $drawing->load('client');
        $clients = Client::orderBy('name')->get();

        $today = now()->format('Y-m-d');

        // この図番の現在有効な作業単価（適用期間内・適用フラグON）を作業方法ごとに取得
        $drawing->load([
            'workRates' => function ($q) use ($today) {
                $q->where('active_flg', true)
                    ->where('effective_from', '<=', $today)
                    ->where(function ($q2) use ($today) {
                        $q2->whereNull('effective_to')
                            ->orWhere('effective_to', '>=', $today);
                    })
                    ->with('workMethod')
                    ->orderBy('work_method_id')
                    ->orderBy('effective_from', 'desc');
            },
        ]);

        // 閲覧用に整形。個単価(円/個) = round(1個あたり重量(kg/個) × 客先kg単価(円/kg))
        $weight = (float) $drawing->weight_per_unit;
        $seenMethods = [];
        $workRates = [];

        foreach ($drawing->workRates as $workRate) {
            // 作業方法ごとに最新の有効単価のみ採用
            if (in_array($workRate->work_method_id, $seenMethods, true)) {
                continue;
            }
            $seenMethods[] = $workRate->work_method_id;

            $kgRate = $workRate->rate_employee !== null ? (float) $workRate->rate_employee : null;

            $workRates[] = [
                'work_rate_id' => $workRate->id,
                'work_method_name' => $workRate->workMethod->name ?? '',
                'rate_employee' => $kgRate,
                'unit_price_employee' => $kgRate !== null ? round($weight * $kgRate) : null,
                'rate_contractor' => $workRate->rate_contractor !== null ? (float) $workRate->rate_contractor : null,
                'rate_overtime' => $workRate->rate_overtime !== null ? (float) $workRate->rate_overtime : null,
                'effective_from' => $workRate->effective_from?->format('Y-m-d'),
                'effective_to' => $workRate->effective_to?->format('Y-m-d'),
                'active_flg' => $workRate->active_flg,
            ];
        }

        $drawing->unsetRelation('workRates');

        return Inertia::render('Drawings/Edit', [
            'drawing' => $drawing,
            'clients' => $clients,
            'workRates' => $workRates,
        ]);
    }

    public function update(UpdateDrawingRequest $request, Drawing $drawing)
    {
        $data = $request->validated();
        $data['active_flag'] = $request->has('active_flag') ? $request->active_flag : true;

        // 画像アップロード（新しい画像がアップロードされた場合）
        if ($request->hasFile('image')) {
            // 古い画像を削除
            if ($drawing->image_path) {
                Storage::disk('public')->delete($drawing->image_path);
            }
            $path = $request->file('image')->store('drawings', 'public');
            $data['image_path'] = $path;
        }

        $drawing->update($data);

        return redirect()->route('drawings.index', $this->backFilters($request))
            ->with('success', '図番を更新しました。');
    }

    public function destroy(Request $request, Drawing $drawing)
    {
        if ($drawing->workRecords()->exists() || $drawing->workRates()->exists()) {
            return back()->withErrors([
                'error' => 'この図番は使用されているため削除できません。'
            ]);
        }

        // 画像を削除
        if ($drawing->image_path) {
            Storage::disk('public')->delete($drawing->image_path);
        }

        $drawing->delete();

        return redirect()->route('drawings.index', $this->backFilters($request))
            ->with('success', '図番を削除しました。');
    }
}








