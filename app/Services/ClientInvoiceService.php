<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientInvoice;
use App\Models\StaffInvoice;
use App\Models\StaffInvoiceItem;
use Illuminate\Support\Facades\DB;

/**
 * 客先請求書サービス
 * スタッフ請求書を元に客先請求書を作成・確定する
 */
class ClientInvoiceService
{
    /**
     * 請求書を作成する
     * 選択されたスタッフ請求書の合計から客先請求書を作成
     * 
     * @param int $clientId 客先ID
     * @param array $staffInvoiceIds スタッフ請求書IDの配列
     * @param string $periodFrom 請求期間開始日
     * @param string $periodTo 請求期間終了日
     * @return ClientInvoice
     * @throws \Exception
     */
    public function createInvoice(int $clientId, array $staffInvoiceIds, string $periodFrom, string $periodTo): ClientInvoice
    {
        DB::beginTransaction();
        try {
            $client = Client::findOrFail($clientId);

            // 選択されたスタッフ請求書を取得（確定済みのみ）
            $staffInvoices = StaffInvoice::whereIn('id', $staffInvoiceIds)
                ->where('status', 'fixed') // 確定済みのみ
                ->get();

            if ($staffInvoices->isEmpty()) {
                throw new \Exception('選択されたスタッフ請求書が存在しないか、確定されていません。');
            }

            // 既に他の客先請求書に含まれているスタッフ請求書をチェック
            $alreadyInvoiced = StaffInvoice::whereIn('id', $staffInvoiceIds)
                ->whereHas('clientInvoiceItems')
                ->pluck('id')
                ->toArray();

            if (!empty($alreadyInvoiced)) {
                throw new \Exception('選択されたスタッフ請求書の一部は既に他の客先請求書に含まれています。');
            }

            // スタッフ請求書の合計を計算
            $subtotal = $staffInvoices->sum('total');

            // 差額調整は初期値0
            $adjustmentAmount = 0;
            $adjustmentReason = null;

            // 消費税を計算（客先の設定に応じて、今回は10%固定）
            $taxRate = 0.1;
            $tax = floor($subtotal * $taxRate);

            $total = $subtotal + $adjustmentAmount + $tax;

            // 請求書番号を生成
            $invoiceNumber = $this->generateInvoiceNumber();

            // 客先請求書を作成
            $clientInvoice = ClientInvoice::create([
                'client_id' => $clientId,
                'invoice_number' => $invoiceNumber,
                'issue_date' => now()->format('Y-m-d'),
                'period_from' => $periodFrom,
                'period_to' => $periodTo,
                'subtotal' => $subtotal,
                'adjustment_amount' => $adjustmentAmount,
                'adjustment_reason' => $adjustmentReason,
                'tax' => $tax,
                'total' => $total,
                'status' => 'draft',
            ]);

            // スタッフ請求書との紐付を作成
            foreach ($staffInvoices as $staffInvoice) {
                StaffInvoiceItem::create([
                    'client_invoice_id' => $clientInvoice->id,
                    'staff_invoice_id' => $staffInvoice->id,
                ]);
            }

            DB::commit();

            return $clientInvoice->load(['client', 'staffInvoiceItems.staffInvoice.staff']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 差額調整を更新する
     * 
     * @param ClientInvoice $invoice
     * @param float $adjustmentAmount 差額調整金額
     * @param string|null $adjustmentReason 差額調整理由
     * @return ClientInvoice
     * @throws \Exception
     */
    public function updateAdjustment(ClientInvoice $invoice, float $adjustmentAmount, ?string $adjustmentReason = null): ClientInvoice
    {
        if ($invoice->isFixed()) {
            throw new \Exception('この請求書は既に確定済みです。');
        }

        DB::beginTransaction();
        try {
            // 新しい合計を計算
            $newTotal = $invoice->subtotal + $adjustmentAmount + $invoice->tax;

            $invoice->update([
                'adjustment_amount' => $adjustmentAmount,
                'adjustment_reason' => $adjustmentReason,
                'total' => $newTotal,
            ]);

            DB::commit();

            return $invoice->load(['client', 'staffInvoiceItems.staffInvoice.staff']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 請求書を確定する
     * 
     * @param ClientInvoice $invoice
     * @return ClientInvoice
     * @throws \Exception
     */
    public function fixInvoice(ClientInvoice $invoice): ClientInvoice
    {
        if ($invoice->isFixed()) {
            throw new \Exception('この請求書は既に確定済みです。');
        }

        DB::beginTransaction();
        try {
            $invoice->update([
                'status' => 'fixed',
            ]);

            DB::commit();

            return $invoice->load(['client', 'staffInvoiceItems.staffInvoice.staff']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 請求書番号を生成
     * 形式: CLIENT-YYYY-NNN
     * 
     * @return string
     */
    private function generateInvoiceNumber(): string
    {
        $year = now()->format('Y');
        $prefix = "CLIENT-{$year}-";

        // 今年の最大番号を取得
        $maxNumber = ClientInvoice::where('invoice_number', 'like', $prefix . '%')
            ->orderBy('invoice_number', 'desc')
            ->value('invoice_number');

        if ($maxNumber) {
            // 既存の最大番号から連番を取得
            $number = (int) substr($maxNumber, strlen($prefix)) + 1;
        } else {
            $number = 1;
        }

        return $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);
    }
}










