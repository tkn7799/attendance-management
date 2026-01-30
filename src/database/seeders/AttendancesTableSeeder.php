<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AttendancesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = User::whereIn('email', ['user1@example.com', 'user2@example.com'])->get();
        $period = CarbonPeriod::create('2025-12-01', '2026-01-31');

        foreach ($users as $user) {
            foreach ($period as $date) {
                if ($date->isWeekend()) {
                    continue;
                }

                $clockIn = (clone $date)->setTime(8, rand(30, 59), 0);
                if (rand(0, 1)) $clockIn->setTime(9, rand(0, 30), 0);

                $clockOut = (clone $date)->setTime(18, rand(00, 59), 0);
                if (rand(0, 1)) $clockOut->setTime(19, rand(0, 30), 0);

                $attendance = Attendance::create([
                    'user_id' => $user->id,
                    'date' => $date->format('Y-m-d'),
                    'clock_in' => $clockIn->format('H:i:s'),
                    'clock_out' => $clockOut->format('H:i:s'),
                ]);

                $rest1Start = (clone $date)->setTime(12, rand(0, 10), 0);
                $rest1End = (clone $rest1Start)->addMinutes(rand(45, 60));

                Rest::create([
                    'attendance_id' => $attendance->id,
                    'start_time' => $rest1Start->format('H:i:s'),
                    'end_time' => $rest1End->format('H:i:s'),
                ]);

                if ($date->day === 15) {
                    $rest2Start = (clone $date)->setTime(16, rand(0, 15), 0);
                    $rest2End = (clone $rest2Start)->addMinutes(15);

                    Rest::create([
                        'attendance_id' => $attendance->id,
                        'start_time' => $rest2Start->format('H:i:s'),
                        'end_time' => $rest2End->format('H:i:s'),
                    ]);
                }
            }
        }
    }
}
