<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 作業単価モデル
 * 重量単価の履歴管理を行う
 * 過去データは変更されない（effective_toで管理）
 */
class WorkRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'drawing_id',
        'work_method_id',
        'rate_employee',
        'rate_contractor',
        'rate_overtime',
        'note',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'rate_employee' => 'decimal:2',
        'rate_contractor' => 'decimal:2',
        'rate_overtime' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
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
     * この単価を使用している作業実績
     */
    public function workRecords()
    {
        return $this->hasMany(WorkRecord::class);
    }

    /**
     * スタッフ種別に応じた単価を取得
     * 
     * @param Staff $staff スタッフ
     * @param bool $isOvertime 残業かどうか（個人事業主の場合）
     * @return float
     */
    public function getRateForStaff(Staff $staff, bool $isOvertime = false): float
    {
        if ($staff->isEmployee()) {
            return (float) $this->rate_employee;
        } elseif ($staff->isContractor()) {
            return $isOvertime ? (float) $this->rate_overtime : (float) $this->rate_contractor;
        }
        
        throw new \InvalidArgumentException('スタッフ種別が不正です');
    }
}














