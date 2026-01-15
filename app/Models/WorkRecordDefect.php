<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 不良内訳モデル
 * 作業実績の不良内訳を管理
 */
class WorkRecordDefect extends Model
{
    use HasFactory;

    protected $table = 'work_record_defects';

    protected $fillable = [
        'work_record_id',
        'defect_type_id',
        'defect_quantity',
    ];

    /**
     * 所属する作業実績
     */
    public function workRecord()
    {
        return $this->belongsTo(WorkRecord::class);
    }

    /**
     * 不良種類
     */
    public function defectType()
    {
        return $this->belongsTo(DefectType::class);
    }
}













