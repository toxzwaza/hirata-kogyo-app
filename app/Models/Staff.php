<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

/**
 * スタッフモデル
 * 正社員・個人事業主のスタッフ情報を管理
 */
class Staff extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'staff';

    protected $fillable = [
        'staff_type_id',
        'name',
        'name_kana',
        'login_id',
        'password',
        'address',
        'postal_code',
        'tel',
        'email',
        'tax_type',
        'birth_date',
        'active_flag',
        'admin_flg',
        'bank_name',
        'branch_name',
        'account_type',
        'account_number',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'active_flag' => 'boolean',
        'admin_flg' => 'boolean',
    ];

    /**
     * 認証に使用するカラム名を取得
     */
    public function getAuthIdentifierName(): string
    {
        return 'login_id';
    }

    /**
     * パスワードをハッシュ化して保存
     */
    public function setPasswordAttribute($value)
    {
        // 空の値の場合はハッシュ化しない（既存のパスワードを保持）
        if (empty($value)) {
            return;
        }
        $this->attributes['password'] = Hash::make($value);
    }

    /**
     * スタッフ種別
     */
    public function staffType()
    {
        return $this->belongsTo(StaffType::class);
    }

    /**
     * 作業実績
     */
    public function workRecords()
    {
        return $this->hasMany(WorkRecord::class);
    }

    /**
     * スタッフ請求書
     */
    public function staffInvoices()
    {
        return $this->hasMany(StaffInvoice::class);
    }

    /**
     * 正社員かどうか
     */
    public function isEmployee(): bool
    {
        return $this->staffType->name === '正社員';
    }

    /**
     * 個人事業主かどうか
     */
    public function isContractor(): bool
    {
        return $this->staffType->name === '個人事業主';
    }
}


