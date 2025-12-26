<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * 作業実績モデル
 * 作業実績を管理
 */
class WorkRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'drawing_id',
        'work_method_id',
        'staff_id',
        'work_rate_id',
        'start_time',
        'end_time',
        'work_minutes',
        'quantity_good',
        'quantity_ng',
        'memo',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * 所属する図番
     */
    public function drawing()
    {
        return $this->belongsTo(Drawing::class);
    }

    /**
     * 所属する作業方法
     */
    public function workMethod()
    {
        return $this->belongsTo(WorkMethod::class);
    }

    /**
     * 作業したスタッフ
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * 使用した作業単価（スナップショット）
     */
    public function workRate()
    {
        return $this->belongsTo(WorkRate::class);
    }

    /**
     * 不良内訳
     */
    public function defects()
    {
        return $this->hasMany(WorkRecordDefect::class);
    }

    /**
     * スタッフ請求書明細
     */
    public function staffInvoiceDetails()
    {
        return $this->hasMany(StaffInvoiceDetail::class);
    }

    /**
     * 総数量（良品 + 不良）
     */
    public function getTotalQuantityAttribute(): int
    {
        return $this->quantity_good + $this->quantity_ng;
    }

    /**
     * 総重量（kg）を計算
     * 重量 = 数量 × 1個あたり重量
     */
    public function getTotalWeightAttribute(): float
    {
        return $this->total_quantity * $this->drawing->weight_per_unit;
    }

    /**
     * この実績が請求書に含まれているか
     */
    public function isInvoiced(): bool
    {
        return $this->staffInvoiceDetails()->exists();
    }

    /**
     * 残業かどうかを判定
     * 18時以降の作業は残業とみなす
     */
    public function isOvertime(): bool
    {
        $startHour = Carbon::parse($this->start_time)->hour;
        return $startHour >= 18;
    }
}






