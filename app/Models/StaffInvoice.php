<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * スタッフ請求書モデル
 * スタッフから自社への請求書を管理
 * 確定後（fixed）は編集不可
 */
class StaffInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'invoice_number',
        'issue_date',
        'period_from',
        'period_to',
        'subtotal',
        'tax',
        'total',
        'status',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'period_from' => 'date',
        'period_to' => 'date',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * 請求対象のスタッフ
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * 請求書明細
     */
    public function details()
    {
        return $this->hasMany(StaffInvoiceDetail::class);
    }

    /**
     * 客先請求書への紐付
     */
    public function clientInvoiceItems()
    {
        return $this->hasMany(StaffInvoiceItem::class);
    }

    /**
     * 確定済みかどうか
     */
    public function isFixed(): bool
    {
        return $this->status === 'fixed' || $this->status === 'paid';
    }

    /**
     * 編集可能かどうか
     */
    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }
}









