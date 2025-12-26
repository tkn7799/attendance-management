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
        $date = $request->query('date', Carbon::today()->format('Y-m-d'));
        $attendances = Attendance::with('user', 'rests')->where('date', $date)->get();
        return view('admin.attendance.daily', compact('attendances', 'date'));
    }

    // スタッフ別月次勤怠
    public function staffMonthlyList($id, Request $request)
    {
        $user = User::findOrFail($id);
        $month = $request->query('month', Carbon::today()->format('Y-m'));
        $attendances = Attendance::where('user_id', $id)->where('date', 'like', "$month%")->get();
        return view('admin.attendance.staff_monthly', compact('user', 'attendances', 'month'));
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
