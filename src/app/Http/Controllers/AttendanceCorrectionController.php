<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Rest;
use App\Models\RestCorrection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceCorrectionController extends Controller
{
    // 修正申請の作成
    public function store(Request $request, $id)
    {
        DB::transaction(function () use ($request, $id) {
            $correction = AttendanceCorrection::create([
                'attendance_id' => $id,
                'user_id' => Auth::id(),
                'revised_clock_in' => $request->clock_in,
                'revised_clock_out' => $request->clock_out,
                'remarks' => $request->remarks,
                'status' => AttendanceCorrection::STATUS_WAITING,
            ]);

            // 休憩の修正案も保存
            foreach ($request->rests as $r) {
                RestCorrection::create([
                    'attendance_correction_id' => $correction->id,
                    'revised_start_time' => $r['start'],
                    'revised_end_time' => $r['end'],
                ]);
            }
        });
        return redirect()->route('correction.request.list');
    }

    // 申請一覧（自分用 / 管理者用）
    public function myRequestList() { /* 省略: 自分のデータを取得 */ }
    public function adminRequestList()
    {
        $requests = AttendanceCorrection::with('user')->orderBy('created_at', 'desc')->get();
        return view('admin.correction.list', compact('requests'));
    }

    // 承認処理（実績テーブルへの反映）
    public function approve($id)
    {
        $corr = AttendanceCorrection::with('restCorrections')->findOrFail($id);

        DB::transaction(function () use ($corr) {
            $attendance = Attendance::findOrFail($corr->attendance_id);
            $attendance->update([
                'clock_in' => $corr->revised_clock_in,
                'clock_out' => $corr->revised_clock_out,
            ]);

            // 休憩データを差し替え
            $attendance->rests()->delete();
            foreach ($corr->restCorrections as $rc) {
                Rest::create([
                    'attendance_id' => $attendance->id,
                    'start_time' => $rc->revised_start_time,
                    'end_time' => $rc->revised_end_time,
                ]);
            }
            $corr->update(['status' => AttendanceCorrection::STATUS_APPROVED, 'approved_by' => Auth::id()]);
        });
        return redirect()->back();
    }
}