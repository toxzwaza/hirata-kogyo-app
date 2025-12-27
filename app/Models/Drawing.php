<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 図番モデル
 * 製品の図番・重量・画像を管理
 */
class Drawing extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'product_name',
        'drawing_number',
        'image_path',
        'weight_per_unit',
        'active_flag',
    ];

    protected $casts = [
        'weight_per_unit' => 'decimal:3',
        'active_flag' => 'boolean',
    ];

    /**
     * 所属する客先
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * この図番の作業単価（履歴管理）
     */
    public function workRates()
    {
        return $this->hasMany(WorkRate::class);
    }

    /**
     * この図番の作業実績
     */
    public function workRecords()
    {
        return $this->hasMany(WorkRecord::class);
    }

    /**
     * 指定日時点で有効な作業単価を取得
     * 
     * @param int $workMethodId 作業方法ID
     * @param \DateTime|string $date 基準日時（デフォルト: 現在日時）
     * @return WorkRate|null
     */
    public function getEffectiveWorkRate(int $workMethodId, $date = null): ?WorkRate
    {
        if ($date === null) {
            $date = now();
        }
        if (is_string($date)) {
            $date = new \DateTime($date);
        }
        $dateStr = $date->format('Y-m-d');

        return $this->workRates()
            ->where('work_method_id', $workMethodId)
            ->where('effective_from', '<=', $dateStr)
            ->where(function ($query) use ($dateStr) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $dateStr);
            })
            ->orderBy('effective_from', 'desc')
            ->first();
    }
}









