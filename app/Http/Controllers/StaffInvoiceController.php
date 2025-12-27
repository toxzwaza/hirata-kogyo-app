<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateStaffInvoiceRequest;
use App\Models\StaffInvoice;
use App\Models\Staff;
use App\Services\StaffInvoiceService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Mpdf\Mpdf;

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
            'details.workRecord.workRate',
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
     */
    public function pdf(StaffInvoice $staffInvoice)
    {
        $staffInvoice->load([
            'staff.staffType',
            'details.workRecord.drawing.client',
            'details.workRecord.workMethod',
            'details.workRecord.workRate',
        ]);

        // 作業実績データを準備
        $workItems = [];
        foreach ($staffInvoice->details as $detail) {
            $workRecord = $detail->workRecord;
            $drawing = $workRecord->drawing;
            $client = $drawing->client;
            $workRate = $workRecord->workRate;
            
            // 日付を取得（作業開始日）
            $date = $workRecord->start_time 
                ? $workRecord->start_time->format('Y年n月j日')
                : '';
            
            // 客先名
            $clientName = $client->name ?? '';
            
            // 品番（図番）
            $drawingNumber = $drawing->drawing_number ?? '';
            
            // 品名
            $productName = $drawing->product_name ?? '';
            
            // 実績数（良品数 + 不良数）
            $quantity = ($workRecord->quantity_good ?? 0) + ($workRecord->quantity_ng ?? 0);
            
            // 1個あたりの重量（kg）
            $weightPerUnit = $drawing->weight_per_unit ?? 0;
            
            // 個単価（1個あたりの重量 × rate_contractor）
            $unitPrice = $weightPerUnit * ($workRate->rate_contractor ?? 0);
            
            // 金額（実績数 × 個単価）
            $amount = $quantity * $unitPrice;
            
            $workItems[] = [
                'date' => $date,
                'client' => $clientName,
                'drawingNumber' => $drawingNumber,
                'productName' => $productName,
                'quantity' => $quantity,
                'unitPrice' => $unitPrice,
                'amount' => $amount,
            ];
        }

        // BladeテンプレートをHTMLに変換
        $html = view('pdf.staff-invoice', [
            'invoice' => $staffInvoice,
            'workItems' => $workItems,
        ])->render();

        // MPDFの設定（日本語フォント対応）
        // lang="ja"が設定されていれば、自動的にsun-extaフォントが使用されます
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15,
            'margin_header' => 0,
            'margin_footer' => 0,
            'default_font_size' => 11,
            'tempDir' => storage_path('app/temp'),
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'useSubstitutions' => true, // フォント置換を有効化
        ]);

        // HTMLをPDFに変換
        $mpdf->WriteHTML($html);

        // ファイル名を生成
        $filename = '請求書_' . $staffInvoice->invoice_number . '_' . date('Ymd') . '.pdf';

        // PDFを文字列として出力
        $pdfContent = $mpdf->Output('', 'S');

        // PDFをダウンロード
        return response()->make($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}






