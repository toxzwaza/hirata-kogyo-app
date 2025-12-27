<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 客先請求書モデル
 * 自社から委託元への請求書を管理
 */
class ClientInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'invoice_number',
        'issue_date',
        'period_from',
        'period_to',
        'subtotal',
        'adjustment_amount',
        'adjustment_reason',
        'tax',
        'total',
        'status',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'period_from' => 'date',
        'period_to' => 'date',
        'subtotal' => 'decimal:2',
        'adjustment_amount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * 請求先の客先
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * 紐付いているスタッフ請求書
     */
    public function staffInvoiceItems()
    {
        return $this->hasMany(StaffInvoiceItem::class);
    }

    /**
     * 確定済みかどうか
     */
    public function isFixed(): bool
    {
        return $this->status === 'fixed' || $this->status === 'issued';
    }
}








