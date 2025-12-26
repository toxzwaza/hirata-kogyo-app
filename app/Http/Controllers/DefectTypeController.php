<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDefectTypeRequest;
use App\Http\Requests\UpdateDefectTypeRequest;
use App\Models\DefectType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DefectTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = DefectType::query()->orderBy('name');

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $defectTypes = $query->paginate(20)->withQueryString();

        return Inertia::render('DefectTypes/Index', [
            'defectTypes' => $defectTypes,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('DefectTypes/Create');
    }

    public function store(StoreDefectTypeRequest $request)
    {
        DefectType::create($request->validated());

        return redirect()->route('defect-types.index')
            ->with('success', '不良種類を登録しました。');
    }

    public function edit(DefectType $defectType)
    {
        return Inertia::render('DefectTypes/Edit', [
            'defectType' => $defectType,
        ]);
    }

    public function update(UpdateDefectTypeRequest $request, DefectType $defectType)
    {
        $defectType->update($request->validated());

        return redirect()->route('defect-types.index')
            ->with('success', '不良種類を更新しました。');
    }

    public function destroy(DefectType $defectType)
    {
        if ($defectType->workRecordDefects()->exists()) {
            return back()->withErrors([
                'error' => 'この不良種類は使用されているため削除できません。'
            ]);
        }

        $defectType->delete();

        return redirect()->route('defect-types.index')
            ->with('success', '不良種類を削除しました。');
    }
}






