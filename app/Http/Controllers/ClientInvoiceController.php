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
use Mpdf\Mpdf;

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
            // 固定の客先名「株式会社○○」を使用（存在しない場合は作成）
            $client = Client::firstOrCreate(
                ['name' => '株式会社○○'],
                ['name_kana' => '']
            );
            
            $invoice = $this->clientInvoiceService->createInvoice(
                $client->id,
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
            'staffInvoiceItems.staffInvoice.details.workRecord.drawing.client',
            'staffInvoiceItems.staffInvoice.details.workRecord.workMethod',
            'staffInvoiceItems.staffInvoice.details.workRecord.workRate',
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
     */
    public function pdf(ClientInvoice $clientInvoice)
    {
        $clientInvoice->load([
            'client',
            'staffInvoiceItems.staffInvoice.staff.staffType',
            'staffInvoiceItems.staffInvoice.details.workRecord.drawing.client',
            'staffInvoiceItems.staffInvoice.details.workRecord.workMethod',
            'staffInvoiceItems.staffInvoice.details.workRecord.workRate',
        ]);

        // 一枚目の客先請求書用アイテム（スタッフごとの集約）
        $clientItems = [];
        foreach ($clientInvoice->staffInvoiceItems as $item) {
            $staffInvoice = $item->staffInvoice;
            $staff = $staffInvoice->staff;
            
            // 日付（期間表記）
            $periodFrom = $staffInvoice->period_from 
                ? $staffInvoice->period_from->format('Y年n月j日')
                : '';
            $periodTo = $staffInvoice->period_to 
                ? $staffInvoice->period_to->format('Y年n月j日')
                : '';
            $date = $periodFrom && $periodTo ? "{$periodFrom} ～ {$periodTo}" : '';
            
            // 項目名（仕上げ加工 氏名）
            $workMethodName = '仕上げ加工'; // 作業方法名
            $staffName = $staff->name ?? '';
            $name = "{$workMethodName} {$staffName}";
            
            // 金額（税抜き）
            $amount = (float)($staffInvoice->subtotal ?? 0);
            
            $clientItems[] = [
                'date' => $date,
                'name' => $name,
                'amount' => $amount,
            ];
        }

        // MPDFの設定
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
            'useSubstitutions' => true,
        ]);

        // 一枚目: 客先請求書（スタッフごとの集約）
        $html1 = view('pdf.client-invoice', [
            'invoice' => $clientInvoice,
            'type' => 'client',
            'clientName' => '株式会社○○',
            'clientPostal' => '',
            'clientAddress' => '',
            'clientItems' => $clientItems,
        ])->render();
        
        $mpdf->WriteHTML($html1);

        // 二枚目以降: スタッフごとの内訳
        $staffGroups = [];
        foreach ($clientInvoice->staffInvoiceItems as $item) {
            $staffInvoice = $item->staffInvoice;
            $staffName = $staffInvoice->staff->name ?? '';
            
            if (!isset($staffGroups[$staffName])) {
                $staffGroups[$staffName] = [];
            }
            
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
                
                // 重量（1個あたりの重量）
                $weightPerUnit = $drawing->weight_per_unit ?? 0;
                
                // 総重量（kg）
                $totalWeight = $quantity * $weightPerUnit;
                
                // 単価（rate_employeeを使用）
                $unitPrice = $workRate->rate_employee ?? 0;
                
                // 金額（総重量 × 単価）
                $amount = $totalWeight * $unitPrice;
                
                $staffGroups[$staffName][] = [
                    'date' => $date,
                    'client' => $clientName,
                    'drawingNumber' => $drawingNumber,
                    'productName' => $productName,
                    'quantity' => $quantity,
                    'weightPerUnit' => $weightPerUnit,
                    'totalWeight' => $totalWeight,
                    'unitPrice' => $unitPrice,
                    'amount' => $amount,
                ];
            }
        }

        // 各スタッフごとにページを追加
        foreach ($staffGroups as $staffName => $workItems) {
            $totalAmount = array_sum(array_column($workItems, 'amount'));
            
            $mpdf->AddPage();
            
            $html2 = view('pdf.client-invoice', [
                'invoice' => $clientInvoice,
                'type' => 'client-detail',
                'clientName' => $staffName,
                'clientPostal' => '',
                'clientAddress' => '',
                'title' => "{$staffName} 様 請求内訳",
                'workItems' => $workItems,
                'totalAmount' => $totalAmount,
            ])->render();
            
            $mpdf->WriteHTML($html2);
        }

        // ファイル名を生成
        $filename = '請求書_' . $clientInvoice->invoice_number . '_' . date('Ymd') . '.pdf';

        // PDFを文字列として出力
        $pdfContent = $mpdf->Output('', 'S');

        // PDFをダウンロード
        return response()->make($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}





