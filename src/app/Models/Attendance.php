<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Attendance extends Model
{
    protected $fillable = ['user_id', 'date', 'clock_in', 'clock_out', 'remarks', 'status'];

    // ステータス定数
    const STATUS_NORMAL = 0;
    const STATUS_PENDING = 1; // 修正申請中

    protected $casts = [
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // リレーション: 休憩実績
    public function rests()
    {
        return $this->hasMany(Rest::class);
    }

    // リレーション: この勤怠に関連する修正申請
    public function corrections()
    {
        return $this->hasMany(AttendanceCorrection::class);
    }

    public function correctionRequests(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class, 'attendance_id');
    }

    // 合計休憩時間を計算 (秒)
    public function getTotalRestDuration()
    {
        $totalSeconds = 0;
        foreach ($this->rests as $rest) {
            if ($rest->start_time && $rest->end_time) {
                $start = Carbon::parse($rest->start_time);
                $end = Carbon::parse($rest->end_time);
                $totalSeconds += $start->diffInSeconds($end);
            }
        }
        return $totalSeconds;
    }
}