<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceCorrection;
use App\Models\Attendance;
use App\Models\Rest;
use Illuminate\Support\Facades\DB;

class AdminAttendanceCorrectionController extends Controller
{
    // 修正申請一覧（タブ切り替え対応）
    public function index(Request $request)
    {
        // クエリパラメータ tab が 'approved' なら承認済み(1)、それ以外は承認待ち(0)
        $status = $request->query('tab', 'pending');
        $statusNum = ($status === 'approved') ? 1 : 0;

        $applications = AttendanceCorrection::with(['user', 'attendance'])
            ->where('status', $statusNum)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.stamp_correction_request.index', compact('applications', 'status'));
    }

    // 修正申請承認画面
    public function show($attendance_correct_request_id)
    {
        $correction = AttendanceCorrection::with(['user', 'attendance', 'restCorrections'])
            ->findOrFail($attendance_correct_request_id);

        return view('admin.stamp_correction_request.show', compact('correction'));
    }

    // 承認処理（実際の勤怠データへの反映）
    public function approve(Request $request, $attendance_correct_request_id)
    {
        $correction = AttendanceCorrection::findOrFail($attendance_correct_request_id);

        DB::transaction(function () use ($correction) {
            // 1. メインの勤怠データを更新
            $attendance = Attendance::findOrFail($correction->attendance_id);
            $attendance->update([
                'clock_in' => $correction->revised_clock_in,
                'clock_out' => $correction->revised_clock_out,
                'remarks'   => $correction->remarks,
            ]);

            // 2. 休憩データを物理削除して、修正後の内容で再作成（入れ替え）
            $attendance->rests()->delete();
            foreach ($correction->restCorrections as $restCor) {
                Rest::create([
                    'attendance_id' => $attendance->id,
                    'start_time' => $restCor->revised_start_time,
                    'end_time' => $restCor->revised_end_time,
                ]);
            }

            // 3. 申請ステータスを承認済み(1)に更新
            $correction->update(['status' => 1]);
        });

        return redirect()->route('admin.correction.list', ['tab' => 'approved'])
                         ->with('success', '申請を承認しました。');
    }
}