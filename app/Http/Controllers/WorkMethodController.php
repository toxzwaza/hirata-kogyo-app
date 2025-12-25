<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkMethodRequest;
use App\Http\Requests\UpdateWorkMethodRequest;
use App\Models\WorkMethod;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WorkMethodController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkMethod::query()->orderBy('name');

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $workMethods = $query->paginate(20)->withQueryString();

        return Inertia::render('WorkMethods/Index', [
            'workMethods' => $workMethods,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('WorkMethods/Create');
    }

    public function store(StoreWorkMethodRequest $request)
    {
        WorkMethod::create($request->validated());

        return redirect()->route('work-methods.index')
            ->with('success', '作業方法を登録しました。');
    }

    public function edit(WorkMethod $workMethod)
    {
        return Inertia::render('WorkMethods/Edit', [
            'workMethod' => $workMethod,
        ]);
    }

    public function update(UpdateWorkMethodRequest $request, WorkMethod $workMethod)
    {
        $workMethod->update($request->validated());

        return redirect()->route('work-methods.index')
            ->with('success', '作業方法を更新しました。');
    }

    public function destroy(WorkMethod $workMethod)
    {
        if ($workMethod->workRecords()->exists() || $workMethod->workRates()->exists()) {
            return back()->withErrors([
                'error' => 'この作業方法は使用されているため削除できません。'
            ]);
        }

        $workMethod->delete();

        return redirect()->route('work-methods.index')
            ->with('success', '作業方法を削除しました。');
    }
}





