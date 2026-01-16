<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Rest;
use App\Models\RestCorrection;
use App\Http\Requests\AttendanceCorrectionRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceCorrectionController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('tab', 'pending');

        $statusNum = ($status === 'approved') ? 1 : 0;

        $applications = AttendanceCorrection::with('attendance')
            ->where('user_id', Auth::id())
            ->where('status', $statusNum)
            ->orderBy('created_at', 'desc')
            ->get();

            return view('attendance.application', compact('applications', 'status'));
    }

    // 修正申請の作成
    public function store(Request $request, $id)
    {
        DB::transaction(function () use ($request, $id) {
            $correction = AttendanceCorrection::create([
                'attendance_id' => $id,
                'user_id' => Auth::id(),
                'revised_clock_in' => $request->revised_clock_in,
                'revised_clock_out' => $request->revised_clock_out,
                'remarks' => $request->remarks,
                'status' => 0,
            ]);

            // 休憩の修正案も保存
            if ($request->has('rest_start')) {
                foreach ($request->rest_start as $index => $startTime) {
                    if (!empty($startTime) && !empty($request->rest_end[$index])) {
                        RestCorrection::create([
                            'attendance_correction_id' => $correction->id,
                            'revised_start_time' => $startTime,
                            'revised_end_time' => $request->rest_end[$index],
                        ]);
                    }
                }
            }
        });

        return redirect()->route('attendance.application', ['tab' => 'pending'])
            ->with('success', '修正申請を送信しました。');
    }
}