<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 作業方法モデル
 * 作業方法のマスタを管理
 */
class WorkMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * この作業方法の作業単価
     */
    public function workRates()
    {
        return $this->hasMany(WorkRate::class);
    }

    /**
     * この作業方法の作業実績
     */
    public function workRecords()
    {
        return $this->hasMany(WorkRecord::class);
    }
}










