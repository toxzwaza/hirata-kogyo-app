<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 不良種類モデル
 * 不良の種類を管理
 */
class DefectType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * この不良種類の不良内訳
     */
    public function workRecordDefects()
    {
        return $this->hasMany(WorkRecordDefect::class);
    }
}



