<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * スタッフ種別モデル
 * 正社員・個人事業主などの種別を管理
 */
class StaffType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * この種別に属するスタッフ
     */
    public function staff()
    {
        return $this->hasMany(Staff::class);
    }
}










