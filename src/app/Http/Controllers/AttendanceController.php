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
        $user = auth()->user();
        $today = now()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)->where('date', $today)->first();

        if ($attendance && is_null($attendance->clock_in)) {
            $attendance = null;
        }

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
        $monthParam = $request->query('month', now()->format('Y-m'));

        $month = \Carbon\Carbon::parse($monthParam);

        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $period = \Carbon\CarbonPeriod::create($startOfMonth, $endOfMonth);
        $attendances = Attendance::where('user_id', auth()->id())
            ->with('rests')
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get()
            ->keyBy(function($item) {
                return \Carbon\Carbon::parse($item->date)->toDateString();
            });

        return view('attendance.list', compact('attendances', 'period', 'month'));
    }

    public function detail(Request $request, $id = null)
    {
        if ($id) {
            $attendance = Attendance::with(['user', 'rests'])->findOrFail($id);
        } else{
            $date = $request->query('date');
            if (!$date) {
                return redirect()->route('attendance.list');
            }

            $attendance = new Attendance([
                'date' => $date,
                'user_id' => auth()->id(),
            ]);

            $attendance->setRelation('user', auth()->user());
            $attendance->setRelation('rests', collect());
        }

        return view('attendance.detail', compact('attendance'));
    }
}
