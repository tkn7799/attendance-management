<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestCorrection extends Model
{
    use HasFactory;

    protected $fillable = ['attendance_correction_id', 'revised_start_time', 'revised_end_time'];

    protected $casts = [
        'revised_start_time' => 'datetime',
        'revised_end_time' => 'datetime',
    ];

    public function attendanceCorrection()
    {
        return $this->belongsTo(AttendanceCorrection::class);
    }
}