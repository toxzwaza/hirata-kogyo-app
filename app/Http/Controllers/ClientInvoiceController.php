<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateClientInvoiceRequest;
use App\Http\Requests\UpdateClientInvoiceAdjustmentRequest;
use App\Models\ClientInvoice;
use App\Models\Client;
use App\Models\StaffInvoice;
use App\Services\ClientInvoiceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * 客先請求書管理コントローラー
 */
class ClientInvoiceController extends Controller
{
    protected $clientInvoiceService;

    public function __construct(ClientInvoiceService $clientInvoiceService)
    {
        $this->clientInvoiceService = $clientInvoiceService;
    }

    /**
     * 客先請求書一覧
     */
    public function index(Request $request)
    {
        $query = ClientInvoice::with(['client'])
            ->orderBy('created_at', 'desc');

        // フィルタリング
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
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
        $clients = Client::orderBy('name')->get();

        return Inertia::render('ClientInvoices/Index', [
            'invoices' => $invoices,
            'clients' => $clients,
            'filters' => $request->only(['client_id', 'status', 'period_from', 'period_to']),
        ]);
    }

    /**
     * 請求書作成画面
     */
    public function create(Request $request)
    {
        $clients = Client::orderBy('name')->get();

        // 確定済みで未使用のスタッフ請求書を取得
        $staffInvoices = StaffInvoice::where('status', 'fixed')
            ->whereDoesntHave('clientInvoiceItems') // まだ客先請求書に含まれていない
            ->with(['staff.staffType'])
            ->orderBy('period_from')
            ->orderBy('created_at')
            ->get();

        // 期間フィルタ
        if ($request->filled('period_from') && $request->filled('period_to')) {
            $staffInvoices = $staffInvoices->filter(function ($invoice) use ($request) {
                return $invoice->period_from >= $request->period_from 
                    && $invoice->period_to <= $request->period_to;
            });
        }

        return Inertia::render('ClientInvoices/Create', [
            'clients' => $clients,
            'staffInvoices' => $staffInvoices,
            'filters' => $request->only(['period_from', 'period_to']),
        ]);
    }

    /**
     * 請求書作成処理
     */
    public function store(CreateClientInvoiceRequest $request)
    {
        try {
            $invoice = $this->clientInvoiceService->createInvoice(
                $request->client_id,
                $request->staff_invoice_ids,
                $request->period_from,
                $request->period_to
            );

            return redirect()->route('client-invoices.show', $invoice)
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
    public function show(ClientInvoice $clientInvoice)
    {
        $clientInvoice->load([
            'client',
            'staffInvoiceItems.staffInvoice.staff.staffType',
            'staffInvoiceItems.staffInvoice.details.workRecord.drawing',
        ]);

        return Inertia::render('ClientInvoices/Show', [
            'invoice' => $clientInvoice,
        ]);
    }

    /**
     * 差額調整更新処理
     */
    public function updateAdjustment(UpdateClientInvoiceAdjustmentRequest $request, ClientInvoice $clientInvoice)
    {
        try {
            $this->clientInvoiceService->updateAdjustment(
                $clientInvoice,
                $request->adjustment_amount,
                $request->adjustment_reason
            );

            return redirect()->route('client-invoices.show', $clientInvoice)
                ->with('success', '差額調整を更新しました。');
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 請求書確定処理
     */
    public function fix(ClientInvoice $clientInvoice)
    {
        try {
            $this->clientInvoiceService->fixInvoice($clientInvoice);

            return redirect()->route('client-invoices.show', $clientInvoice)
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
    public function pdf(ClientInvoice $clientInvoice)
    {
        $clientInvoice->load([
            'client',
            'staffInvoiceItems.staffInvoice.staff.staffType',
            'staffInvoiceItems.staffInvoice.details.workRecord.drawing',
        ]);

        // TODO: PDF生成ライブラリを使用してPDFを生成
        // 例: return PDF::loadView('pdf.client-invoice', ['invoice' => $clientInvoice])->download();
        
        return response()->json([
            'message' => 'PDF出力機能は実装中です。',
            'invoice' => $clientInvoice,
        ]);
    }
}


