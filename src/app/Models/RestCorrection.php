<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestCorrection extends Model
{
    protected $fillable = ['attendance_correction_id', 'revised_start_time', 'revised_end_time'];

    public function attendanceCorrection()
    {
        return $this->belongsTo(AttendanceCorrection::class);
    }
}