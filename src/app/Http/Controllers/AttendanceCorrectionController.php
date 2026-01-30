<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Rest;
use App\Models\RestCorrection;
use App\Http\Requests\AttendanceUpdateRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceCorrectionController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('tab', 'pending');

        $statusNum = ($status === 'approved') ? 1 : 0;

        $applications = AttendanceCorrection::where('user_id', Auth::id())
            ->where('status', $statusNum)
            ->with('attendance')
            ->orderBy('created_at', 'desc')
            ->get();

        $month = now();
        $period = [];
        $attendances = collect();

        return view('attendance.application', compact('applications', 'status'));
    }

    // 修正申請の作成
    public function store(AttendanceUpdateRequest $request, $id = null)
    {
        if ($id) {
            $attendance = Attendance::findOrFail($id);
        } else {
            $attendance = Attendance::where('user_id', auth()->id())
                                    ->where('date', $request->date)
                                    ->first();

            if (!$attendance) {
                $attendance = new Attendance();
                $attendance->user_id = auth()->id();
                $attendance->date = $request->date;
                $attendance->clock_in = null;
                $attendance->clock_out = null;

                $attendance->saveQuietly();
            }
        }

        if ($attendance->corrections()->where('status', 0)->exists()) {
            return redirect()->back()->with('error', '既に修正申請中のため、重ねて申請することはできません。');
        }

        DB::transaction(function () use ($request, $attendance) {
            $correction = AttendanceCorrection::create([
                'attendance_id' => $attendance->id,
                'user_id' => auth()->id(),
                'revised_clock_in' => $request->clock_in,
                'revised_clock_out' => $request->clock_out,
                'remarks' => $request->remarks,
                'status' => 0,
            ]);

            // 休憩の修正案も保存
            if ($request->has('rests')) {
                foreach ($request->rests as $restData) {
                    if (!empty($restData['start']) && !empty($restData['end'])) {
                        $correction->restCorrections()->create([
                            'revised_start_time' => $restData['start'],
                            'revised_end_time' => $restData['end'],
                        ]);
                    }
                }
            }
        });

        return redirect()->route('attendance.application')->with('success', '修正申請を送信しました。');
    }
}