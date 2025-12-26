<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * スタッフ請求書明細モデル
 * 請求書の明細行を管理
 * 金額はスナップショットとして保存される
 */
class StaffInvoiceDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_invoice_id',
        'work_record_id',
        'description',
        'quantity',
        'unit_price',
        'amount',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    /**
     * 所属するスタッフ請求書
     */
    public function staffInvoice()
    {
        return $this->belongsTo(StaffInvoice::class);
    }

    /**
     * 元となった作業実績
     */
    public function workRecord()
    {
        return $this->belongsTo(WorkRecord::class);
    }
}






