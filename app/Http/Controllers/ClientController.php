<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * 客先管理コントローラー
 */
class ClientController extends Controller
{
    /**
     * 客先一覧
     */
    public function index(Request $request)
    {
        $query = Client::query()->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('name_kana', 'like', "%{$search}%");
            });
        }

        $clients = $query->paginate(20)->withQueryString();

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * 客先登録画面
     */
    public function create()
    {
        return Inertia::render('Clients/Create');
    }

    /**
     * 客先登録処理
     */
    public function store(StoreClientRequest $request)
    {
        Client::create($request->validated());

        return redirect()->route('clients.index')
            ->with('success', '客先を登録しました。');
    }

    /**
     * 客先詳細・編集画面
     */
    public function edit(Client $client)
    {
        return Inertia::render('Clients/Edit', [
            'client' => $client,
        ]);
    }

    /**
     * 客先更新処理
     */
    public function update(UpdateClientRequest $request, Client $client)
    {
        $client->update($request->validated());

        return redirect()->route('clients.index')
            ->with('success', '客先を更新しました。');
    }

    /**
     * 客先削除処理
     */
    public function destroy(Client $client)
    {
        // 関連する図番が存在する場合は削除不可
        if ($client->drawings()->exists()) {
            return back()->withErrors([
                'error' => 'この客先に関連する図番が存在するため削除できません。'
            ]);
        }

        $client->delete();

        return redirect()->route('clients.index')
            ->with('success', '客先を削除しました。');
    }
}








