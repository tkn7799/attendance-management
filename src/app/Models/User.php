<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role'];

    // 定数定義
    const ROLE_ADMIN = 1;
    const ROLE_USER = 2;

    // リレーション: 勤怠実績
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    // リレーション: 修正申請 (自分が申請したもの)
    public function corrections()
    {
        return $this->hasMany(AttendanceCorrection::class);
    }

    // ヘルパー: 管理者かどうか
    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
