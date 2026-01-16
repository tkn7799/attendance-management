<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminAttendanceController extends Controller
{
    // PG08: 日次勤怠一覧（全スタッフ）
    public function dailyList(Request $request)
    {
        $dateParam = $request->query('date', Carbon::today()->toDateString());
        $date = Carbon::parse($dateParam);

        $attendances = Attendance::with('user', 'rests')
            ->whereDate('date', $date->toDateString())
            ->get();

        return view('admin.attendance.daily', compact('attendances', 'date'));
    }

    // PG10: スタッフ一覧
    public function staffList()
    {
        $users = User::where('role', 'user')->get(); // 一般ユーザーのみ取得
        return view('admin.attendance.staff_list', compact('users'));
    }

    // PG11: スタッフ別勤怠一覧（月次）
    public function staffMonthlyList($id, Request $request)
    {
        $user = User::findOrFail($id);
        $monthParam = $request->query('month', Carbon::today()->format('Y-m'));
        $month = Carbon::parse($monthParam . '-01');

        $attendances = Attendance::with('rests')
            ->where('user_id', $id)
            ->whereMonth('date', $month->month)
            ->whereYear('date', $month->year)
            ->orderBy('date', 'asc')
            ->get();

        return view('admin.attendance.staff_monthly', compact('user', 'attendances', 'month'));
    }

    // PG09: 勤怠詳細（閲覧用）
    public function show($id)
    {
        $attendance = Attendance::with(['user', 'rests'])->findOrFail($id);
        return view('admin.attendance.detail', compact('attendance'));
    }

    // CSV出力
    public function exportCsv($id, Request $request)
    {
        $user = User::findOrFail($id);
        $monthParam = $request->query('month', Carbon::today()->format('Y-m'));
        $startOfMonth = Carbon::parse($monthParam . '-01')->startOfMonth();
        $endOfMonth = Carbon::parse($monthParam . '-01')->endOfMonth();

        $attendances = Attendance::where('user_id', $id)
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get()
            ->keyBy(function($item) {
                return is_string($item->date) ? $item->date : $item->date->format('Y-m-d');
            });

        $response = new StreamedResponse(function () use ($startOfMonth, $endOfMonth, $attendances) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['日付', '出勤時間', '退勤時間', '休憩時間合計', '勤務時間合計']);

            $period = CarbonPeriod::create($startOfMonth, $endOfMonth);
            foreach ($period as $date) {
                $dateKey = $date->toDateString();
                $attendance = $attendances->get($dateKey);

                fputcsv($handle, [
                    $date->format('Y/m/d'),
                    $attendance && $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '',
                    $attendance && $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '',
                    $attendance->total_rest_duration ?? '',
                    $attendance->total_work_duration ?? '',
                ]);
            }


            fclose($handle);
        });

        $fileName = "attendance_{$user->name}_{$monthParam}.csv";
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        return $response;
    }
}