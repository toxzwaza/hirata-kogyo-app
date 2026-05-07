<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
        'mobile_login_token',
        'address',
        'tel',
        'email',
        'tax_type',
        'birth_date',
        'active_flag',
        'bank_name',
        'branch_name',
        'account_type',
        'account_number',
    ];

    protected $hidden = [
        'password',
        'mobile_login_token',
    ];

    protected $casts = [
        'active_flag' => 'boolean',
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

    /**
     * スマホ自動ログイン用トークンを再発行して返す
     */
    public function regenerateMobileLoginToken(): string
    {
        do {
            $token = Str::random(48);
        } while (static::where('mobile_login_token', $token)->where('id', '!=', $this->id)->exists());

        $this->forceFill(['mobile_login_token' => $token])->save();

        return $token;
    }

    protected static function booted(): void
    {
        static::creating(function (Staff $staff): void {
            if (empty($staff->mobile_login_token)) {
                do {
                    $token = Str::random(48);
                } while (static::where('mobile_login_token', $token)->exists());
                $staff->mobile_login_token = $token;
            }
        });
    }
}


