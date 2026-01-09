<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use App\Models\Staff;
use App\Models\StaffType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $query = Staff::with('staffType')->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('login_id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('staff_type_id')) {
            $query->where('staff_type_id', $request->staff_type_id);
        }

        if ($request->has('active_flag')) {
            $query->where('active_flag', $request->active_flag);
        }

        $staffList = $query->paginate(20)->withQueryString();
        $staffTypes = StaffType::orderBy('name')->get();

        return Inertia::render('Staff/Index', [
            'staffList' => $staffList,
            'staffTypes' => $staffTypes,
            'filters' => $request->only(['search', 'staff_type_id', 'active_flag']),
        ]);
    }

    public function create()
    {
        $staffTypes = StaffType::orderBy('name')->get();

        return Inertia::render('Staff/Create', [
            'staffTypes' => $staffTypes,
        ]);
    }

    public function store(StoreStaffRequest $request)
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $data['active_flag'] = $request->has('active_flag') ? $request->active_flag : true;

        Staff::create($data);

        return redirect()->route('staff.index')
            ->with('success', 'スタッフを登録しました。');
    }

    public function edit(Staff $staff)
    {
        $staff->load('staffType');
        $staffTypes = StaffType::orderBy('name')->get();

        return Inertia::render('Staff/Edit', [
            'staff' => $staff,
            'staffTypes' => $staffTypes,
        ]);
    }

    public function update(UpdateStaffRequest $request, Staff $staff)
    {
        $data = $request->validated();
        
        // パスワードが入力されている場合のみ更新
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $data['active_flag'] = $request->has('active_flag') ? $request->active_flag : true;

        $staff->update($data);

        return redirect()->route('staff.index')
            ->with('success', 'スタッフを更新しました。');
    }

    public function destroy(Staff $staff)
    {
        if ($staff->workRecords()->exists() || $staff->staffInvoices()->exists()) {
            return back()->withErrors([
                'error' => 'このスタッフは使用されているため削除できません。'
            ]);
        }

        $staff->delete();

        return redirect()->route('staff.index')
            ->with('success', 'スタッフを削除しました。');
    }
}










