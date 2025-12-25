<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateStaffInvoiceRequest;
use App\Models\StaffInvoice;
use App\Models\Staff;
use App\Services\StaffInvoiceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * スタッフ請求書管理コントローラー
 */
class StaffInvoiceController extends Controller
{
    protected $staffInvoiceService;

    public function __construct(StaffInvoiceService $staffInvoiceService)
    {
        $this->staffInvoiceService = $staffInvoiceService;
    }

    /**
     * スタッフ請求書一覧
     */
    public function index(Request $request)
    {
        $query = StaffInvoice::with(['staff.staffType'])
            ->orderBy('created_at', 'desc');

        // フィルタリング
        if ($request->filled('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('period_from')) {
            $query->where('period_to', '>=', $request->period_from);
        }
        if ($request->filled('period_to')) {
            $query->where('period_from', '<=', $request->period_to);
        }

        $invoices = $query->paginate(20)->withQueryString();

        // マスタデータ
        $staffList = Staff::where('active_flag', true)
            ->with('staffType')
            ->orderBy('name')
            ->get();

        return Inertia::render('StaffInvoices/Index', [
            'invoices' => $invoices,
            'staffList' => $staffList,
            'filters' => $request->only(['staff_id', 'status', 'period_from', 'period_to']),
        ]);
    }

    /**
     * 請求書作成画面
     */
    public function create()
    {
        $staffList = Staff::where('active_flag', true)
            ->with('staffType')
            ->orderBy('name')
            ->get();

        return Inertia::render('StaffInvoices/Create', [
            'staffList' => $staffList,
        ]);
    }

    /**
     * 請求書作成処理
     */
    public function store(CreateStaffInvoiceRequest $request)
    {
        try {
            $invoice = $this->staffInvoiceService->createInvoice(
                $request->staff_id,
                $request->period_from,
                $request->period_to
            );

            return redirect()->route('staff-invoices.show', $invoice)
                ->with('success', '請求書を作成しました。');
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => $e->getMessage()
            ])->withInput();
        }
    }

    /**
     * 請求書確認画面
     */
    public function show(StaffInvoice $staffInvoice)
    {
        $staffInvoice->load([
            'staff.staffType',
            'details.workRecord.drawing.client',
            'details.workRecord.workMethod',
        ]);

        return Inertia::render('StaffInvoices/Show', [
            'invoice' => $staffInvoice,
        ]);
    }

    /**
     * 請求書確定処理
     */
    public function fix(StaffInvoice $staffInvoice)
    {
        try {
            $this->staffInvoiceService->fixInvoice($staffInvoice);

            return redirect()->route('staff-invoices.show', $staffInvoice)
                ->with('success', '請求書を確定しました。');
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * PDF出力
     * 注: PDFライブラリ（例: dompdf, tcpdf）の実装が必要
     */
    public function pdf(StaffInvoice $staffInvoice)
    {
        $staffInvoice->load([
            'staff.staffType',
            'details.workRecord.drawing.client',
            'details.workRecord.workMethod',
        ]);

        // TODO: PDF生成ライブラリを使用してPDFを生成
        // 例: return PDF::loadView('pdf.staff-invoice', ['invoice' => $staffInvoice])->download();
        
        return response()->json([
            'message' => 'PDF出力機能は実装中です。',
            'invoice' => $staffInvoice,
        ]);
    }
}





