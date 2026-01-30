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
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
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

    public function attendanceCorrection()
    {
        return $this->hasOne(AttendanceCorrection::class, 'attendance_id')
                    ->latestOfMany('id', 'max');
    }

    // 合計休憩時間を計算 (秒)
    public function getTotalRestDurationAttribute()
    {
        $totalSeconds = 0;
        foreach ($this->rests as $rest) {
            if ($rest->start_time && $rest->end_time) {
                $totalSeconds += \Carbon\Carbon::parse($rest->start_time)->diffInSeconds(\Carbon\Carbon::parse($rest->end_time));
            }
        }

        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    public function getTotalWorkDurationAttribute()
    {
        if (!$this->clock_in || !$this->clock_out) {
            return '';
        }

        $clockIn = \Carbon\Carbon::parse($this->clock_in);
        $clockOut = \Carbon\Carbon::parse($this->clock_out);

        $totalWorkSeconds = $clockIn->diffInSeconds($clockOut);

        // 休憩時間を差し引く
        $totalRestSeconds = 0;
        foreach ($this->rests as $rest) {
            if ($rest->start_time && $rest->end_time) {
                $totalRestSeconds += \Carbon\Carbon::parse($rest->start_time)->diffInSeconds(\Carbon\Carbon::parse($rest->end_time));
            }
        }

        $actualWorkSeconds = $totalWorkSeconds - $totalRestSeconds;

        if ($actualWorkSeconds < 0) $actualWorkSeconds = 0;

        $hours = floor($actualWorkSeconds / 3600);
        $minutes = floor(($actualWorkSeconds % 3600) / 60);

        return sprintf('%02d:%02d', $hours, $minutes);
    }
}