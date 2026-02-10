<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Http\Requests\AdminAttendanceUpdateRequest;

class AdminAttendanceController extends Controller
{
    // 日次勤怠一覧（全スタッフ）
    public function dailyList(Request $request)
    {
        $dateParam = $request->query('date', Carbon::today()->toDateString());
        $date = Carbon::parse($dateParam);

        $users = User::where('role', 2)
            ->with(['attendances' => function($query) use ($date) {
                $query->whereDate('date', $date->toDateString())->with('rests');
            }])
            ->get();

        return view('admin.attendance.daily', compact('users', 'date'));
    }

    // スタッフ一覧
    public function staffList()
    {
        $users = User::where('role', 2)->get();
        return view('admin.attendance.staff_list', compact('users'));
    }

    // スタッフ別勤怠一覧（月次）
    public function staffMonthlyList($id, Request $request)
    {
        $user = User::findOrFail($id);
        $monthParam = $request->query('month', Carbon::today()->format('Y-m'));
        $currentMonth = Carbon::parse($monthParam . '-01');

        $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

        $attendanceData = Attendance::with('rests')
            ->where('user_id', $id)
            ->whereMonth('date', $currentMonth->month)
            ->whereYear('date', $currentMonth->year)
            ->get()
            ->keyBy(function($item) {
                return Carbon::parse($item->date)->format('Y-m-d');
            });

        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();
        $period = CarbonPeriod::create($startOfMonth, $endOfMonth);

        $attendances = [];
        foreach ($period as $date) {
            $dateString = $date->toDateString();
            $attendances[] = $attendanceData->has($dateString)
                ? $attendanceData->get($dateString)
                : new Attendance(['date' => $dateString, 'user_id' => $id]);
        }

        return view('admin.attendance.staff_monthly', compact('user', 'attendances', 'currentMonth', 'prevMonth', 'nextMonth'));
    }

    // 勤怠詳細（閲覧用）
    public function show(Request $request, $id = null)
    {
        if ($id) {
            $attendance = Attendance::with(['user', 'rests', 'attendanceCorrection.restCorrections'])->findOrFail($id);

            $isPending = $attendance->attendanceCorrection && $attendance->attendanceCorrection->status === 0;

            if ($isPending) {
                session()->now('info', '承認待ちのため修正はできません。');
            }
        } else {
            $isPending = false;
            $userId = $request->query('user_id');
            $date = $request->query('date');

            $user = User::findOrFail($userId);

            $attendance = new Attendance([
                'user_id' => $userId,
                'date' => $date,
                'clock_in' => null,
                'clock_out' => null,
            ]);
            $attendance->setRelation('user', $user);
            $attendance->setRelation('rests', collect());
        }
        return view('admin.attendance.detail', compact('attendance', 'isPending'));
    }

    public function update(AdminAttendanceUpdateRequest $request, $id = null)
    {
        $attendance = $id ? Attendance::findOrFail($id) : new Attendance();

        DB::transaction(function () use ($request, $attendance) {
            if (!$attendance->exists) {
                $attendance->user_id = $request->user_id;
                $attendance->date = $request->date;
            }
            $attendance->clock_in = $request->clock_in;
            $attendance->clock_out = $request->clock_out;
            $attendance->remarks = $request->remarks;
            $attendance->save();

            $attendance->rests()->delete();

            $correction = \App\Models\AttendanceCorrection::updateOrCreate(
                ['attendance_id' => $attendance->id],
                [
                    'user_id' => $attendance->user_id,
                    'revised_clock_in' => $request->clock_in,
                    'revised_clock_out' => $request->clock_out,
                    'remarks' => $request->remarks,
                    'status' => 1,
                ]
            );

            $correction->restCorrections()->delete();

            if ($request->has('rests')) {
                foreach ($request->rests as $restData) {
                    if (!empty($restData['start']) && !empty($restData['end'])) {
                        $attendance->rests()->create([
                            'start_time' => $restData['start'],
                            'end_time' => $restData['end'],
                        ]);

                        $correction->restCorrections()->create([
                            'revised_start_time' => $restData['start'],
                            'revised_end_time' => $restData['end'],
                        ]);
                    }
                }
            }
        });

        return redirect()->back()->with('success', '勤怠情報を更新しました');
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
                    $attendance ? $attendance->total_rest_duration : '',
                    $attendance ? $attendance->total_work_duration : '',
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