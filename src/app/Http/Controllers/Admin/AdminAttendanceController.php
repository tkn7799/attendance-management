<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

class AdminAttendanceController extends Controller
{
    //日次勤怠一覧
    public function dailyList(Request $request)
    {
        $dateParam = $request->query('date', Carbon::today()->toDateString());
        $date = Carbon::parse($dateParam);
        $attendances = Attendance::with('user', 'rests')
            ->where('date', $date->toDateString())
            ->get();
        return view('admin.attendance.daily', compact('attendances', 'date'));
    }

    // スタッフ別月次勤怠
    public function staffMonthlyList($id, Request $request)
    {
        $user = User::findOrFail($id);
        $monthParam = $request->query('month', Carbon::today()->format('Y-m'));
        $month = Carbon::parse($monthParam . '-01');
        $attendances = Attendance::with('rests')
            ->where('user_id', $id)
            ->where('date', 'like', $month->format('Y-m') . '%')
            ->get();
        return view('admin.attendance.staff_monthly', compact('user', 'attendances', 'month'));
    }

    public function detail($id)
    {
        $attendance = Attendance::with(['user', 'rests'])->findOrFail($id);
        if (!$attendance->date instanceof \Carbon\Carbon) {
            $attendance->date = \Carbon\Carbon::parse($attendance->date);
        }
        return view('admin.attendance.detail', compact('attendance'));
    }

    public function update(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        $attendance->clock_in = $request->input('clock_in');
        $attendance->clock_out = $request->input('clock_out');
        $attendance->save();

        // 休憩時間の更新
        $restsData = $request->input('rests', []);
        foreach ($restsData as $restId => $restInfo) {
            $rest = $attendance->rests()->where('id', $restId)->first();
            if ($rest) {
                $rest->start_time = $restInfo['start_time'];
                $rest->end_time = $restInfo['end_time'];
                $rest->save();
            }
        }

        return redirect()->route('admin.attendance.list')->with('success', '勤怠情報を更新しました');
    }

    public function show($id)
    {
        $attendance = Attendance::with(['user', 'rests'])->findOrFail($id);
        return view('admin.attendance.detail', compact('attendance'));
    }

    // CSV出力
    public function exportCsv($id, Request $request)
    {
        $month = $request->query('month');
        $attendances = Attendance::where('user_id', $id)->where('date', 'like', "$month%")->get();

        $callback = function() use ($attendances) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['日付', '出勤時間', '退勤時間', '休憩時間合計']);
            foreach ($attendances as $a) {
                fputcsv($file, [$a->date, $a->clock_in, $a->clock_out, $a->getTotalRestDuration()]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=attendance_{$month}.csv",
        ]);
    }
}
