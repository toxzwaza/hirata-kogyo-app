<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDrawingRequest;
use App\Http\Requests\UpdateDrawingRequest;
use App\Models\Drawing;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DrawingController extends Controller
{
    public function index(Request $request)
    {
        $query = Drawing::with('client')->orderBy('drawing_number');

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

        if ($request->has('active_flag')) {
            $query->where('active_flag', $request->active_flag);
        }

        $drawings = $query->paginate(20)->withQueryString();
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

        return Inertia::render('Drawings/Edit', [
            'drawing' => $drawing,
            'clients' => $clients,
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

        return redirect()->route('drawings.index')
            ->with('success', '図番を更新しました。');
    }

    public function destroy(Drawing $drawing)
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

        return redirect()->route('drawings.index')
            ->with('success', '図番を削除しました。');
    }
}


