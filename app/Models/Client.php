<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 客先モデル
 * 委託元の客先情報を管理
 */
class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_kana',
    ];

    /**
     * この客先に属する図番
     */
    public function drawings()
    {
        return $this->hasMany(Drawing::class);
    }

    /**
     * この客先への請求書
     */
    public function clientInvoices()
    {
        return $this->hasMany(ClientInvoice::class);
    }
}
















