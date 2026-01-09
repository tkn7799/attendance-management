<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Rest;
use Carbon\Carbon;
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
        $month = Carbon::parse($monthParam . '-01');
        $attendances = Attendance::where('user_id', Auth::id())
            ->where('date', 'like', $month->format('Y-m') . '%')
            ->orderBy('date', 'asc')
            ->get();

        return view('attendance.list', compact('attendances', 'month'));
    }

    public function detail($id)
    {
        $attendance = Attendance::findOrFail($id);

        $isPending = $attendance->correctionRequests()
            ->where('status', 1)
            ->exists();

        return view('attendance.detail', compact('attendance', 'isPending'));
    }
}
