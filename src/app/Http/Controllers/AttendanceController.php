<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Rest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today()->format('Y-m-d');
        $attendance = Attendance::where('user_id', $user->id)->where('date', $today)->first();

        return view('attendance.index', compact('attendance'));
    }

    public function clockIn()
    {
        Attendance::create([
            'user_id' => Auth::id(),
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->format('H:i:s'),
        ]);
        return redirect()->back();
    }

    public function restStart()
    {
        $attendance = Attendance::where('user_id', Auth::id())->where('date', Carbon::today())->first();
        $attendance->rests()->create(['start_time' => Carbon::now()->format('H:i:s')]);
        return redirect()->back();
    }

    public function restEnd()
    {
        $attendance = Attendance::where('user_id', Auth::id())->where('date', Carbon::today())->first();
        $rest = $attendance->rests()->whereNull('end_time')->latest()->first();
        if ($rest) $rest->update(['end_time' => Carbon::now()->format('H:i:s')]);
        return redirect()->back();
    }

    public function clockOut()
    {
        $attendance = Attendance::where('user_id', Auth::id())->where('date', Carbon::today())->first();
        $attendance->update(['clock_out' => Carbon::now()->format('H:i:s')]);
        return redirect()->back();
    }

    public function list(Request $request)
    {
        $monthParam = $request->query('month', Carbon::today()->format('Y-m'));

        $month = \Carbon\Carbon::parse($monthParam . '-01');

        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $datePeriod = CarbonPeriod::create($startOfMonth, $endOfMonth);

        $attendances = Attendance::where('user_id', Auth::id())
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get()
            ->keyBy(function($item) {
                return is_string($item->date) ? $item->date : $item->date->format('Y-m-d');
            });

        return view('attendance.list', compact('month', 'datePeriod', 'attendances'));
    }

    public function detail($id)
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $id)) {
            $attendance = Attendance::where('user_id', Auth::id())
                ->where('date', $id)
                ->firstOrFail();
        } else {
            $attendance = Attendance::with(['user', 'rests'])->findOrFail($id);
        }

        $isPending = $attendance->correctionRequests()
            ->where('status', 0)
            ->exists();

        return view('attendance.detail', compact('attendance', 'isPending'));
    }
}
