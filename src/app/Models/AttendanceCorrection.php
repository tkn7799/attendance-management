<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrection extends Model
{
    protected $fillable = ['attendance_id', 'user_id', 'revised_clock_in', 'revised_clock_out', 'remarks', 'status', 'approved_by'];

    // ステータス定数
    const STATUS_WAITING = 0;
    const STATUS_APPROVED = 1;

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function restCorrections()
    {
        return $this->hasMany(RestCorrection::class);
    }
}