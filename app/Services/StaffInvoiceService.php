<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\StaffInvoice;
use App\Models\StaffInvoiceDetail;
use App\Models\WorkRecord;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * スタッフ請求書サービス
 * 請求書の作成・確定ロジックを担当
 */
class StaffInvoiceService
{
    /**
     * 請求書を作成する
     * 
     * @param int $staffId スタッフID
     * @param string $periodFrom 請求期間開始日
     * @param string $periodTo 請求期間終了日
     * @return StaffInvoice
     * @throws \Exception
     */
    public function createInvoice(int $staffId, string $periodFrom, string $periodTo): StaffInvoice
    {
        DB::beginTransaction();
        try {
            $staff = Staff::findOrFail($staffId);

            // 請求期間内の未請求の作業実績を取得
            $workRecords = WorkRecord::where('staff_id', $staffId)
                ->whereDate('start_time', '>=', $periodFrom)
                ->whereDate('start_time', '<=', $periodTo)
                ->whereDoesntHave('staffInvoiceDetails') // 既に請求書に含まれていない
                ->with(['drawing', 'workMethod', 'workRate'])
                ->orderBy('start_time')
                ->get();

            if ($workRecords->isEmpty()) {
                throw new \Exception('請求期間内に未請求の作業実績がありません。');
            }

            // 請求書番号を生成（例: STAFF-2025-001）
            $invoiceNumber = $this->generateInvoiceNumber();

            // 請求書を作成
            $invoice = StaffInvoice::create([
                'staff_id' => $staffId,
                'invoice_number' => $invoiceNumber,
                'issue_date' => now()->format('Y-m-d'),
                'period_from' => $periodFrom,
                'period_to' => $periodTo,
                'subtotal' => 0,
                'tax' => 0,
                'total' => 0,
                'status' => 'draft',
            ]);

            // 明細を作成し、金額を計算
            $subtotal = 0;
            foreach ($workRecords as $workRecord) {
                // 重量を計算（kg）
                $weight = $workRecord->total_weight;

                // 単価を取得（スタッフ種別と残業かどうかで判定）
                $isOvertime = $workRecord->isOvertime();
                $unitPrice = $workRecord->workRate->getRateForStaff($staff, $isOvertime);

                // 金額を計算（重量 × 単価）
                $amount = $weight * $unitPrice;

                // 明細説明を生成
                $description = sprintf(
                    '%s %s %s',
                    $workRecord->drawing->drawing_number,
                    $workRecord->workMethod->name,
                    $workRecord->start_time->format('Y/m/d H:i')
                );

                // 明細を作成
                StaffInvoiceDetail::create([
                    'staff_invoice_id' => $invoice->id,
                    'work_record_id' => $workRecord->id,
                    'description' => $description,
                    'quantity' => $weight,
                    'unit_price' => $unitPrice,
                    'amount' => $amount,
                ]);

                $subtotal += $amount;
            }

            // 消費税を計算（課税区分に応じて）
            $tax = 0;
            if ($staff->tax_type === 'taxable') {
                // 消費税率10%（将来的に設定可能にする）
                $taxRate = 0.1;
                $tax = floor($subtotal * $taxRate);
            }

            $total = $subtotal + $tax;

            // 請求書の金額を更新
            $invoice->update([
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
            ]);

            DB::commit();

            return $invoice->load(['staff', 'details.workRecord']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 請求書を確定する
     * 確定後は編集不可になる
     * 
     * @param StaffInvoice $invoice
     * @return StaffInvoice
     * @throws \Exception
     */
    public function fixInvoice(StaffInvoice $invoice): StaffInvoice
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

            return $invoice->load(['staff', 'details.workRecord']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 請求書番号を生成
     * 形式: STAFF-YYYY-NNN
     * 
     * @return string
     */
    private function generateInvoiceNumber(): string
    {
        $year = now()->format('Y');
        $prefix = "STAFF-{$year}-";

        // 今年の最大番号を取得
        $maxNumber = StaffInvoice::where('invoice_number', 'like', $prefix . '%')
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








