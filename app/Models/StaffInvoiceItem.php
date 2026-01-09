<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * スタッフ請求書紐付モデル
 * 客先請求書とスタッフ請求書の紐付を管理
 */
class StaffInvoiceItem extends Model
{
    use HasFactory;

    protected $table = 'staff_invoice_items';

    protected $fillable = [
        'client_invoice_id',
        'staff_invoice_id',
    ];

    /**
     * 所属する客先請求書
     */
    public function clientInvoice()
    {
        return $this->belongsTo(ClientInvoice::class);
    }

    /**
     * 紐付いているスタッフ請求書
     */
    public function staffInvoice()
    {
        return $this->belongsTo(StaffInvoice::class);
    }
}











