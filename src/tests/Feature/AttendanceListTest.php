<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;
    /** @test */
    public function 自分が行った勤怠情報が全て表示されている()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // 当月のデータを作成
        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::now()->format('Y-m-d'),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00'
        ]);

        // 他人の勤怠データを作成
        Attendance::create([
            'user_id' => $otherUser->id,
            'date' => Carbon::now()->format('Y-m-d'),
            'clock_in' => '10:00:00',
            'clock_out' => '18:00:00'
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        // データが表示されているか
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertDontSee('10:00'); // 他人のデータが表示されていないことを確認
    }

    /** @test */
    public function 勤怠一覧画面に遷移した際に現在の月が表示される()
    {
        $user = User::factory()->create();

        $expectedMonth = Carbon::now()->format('Y/m');

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee($expectedMonth);
    }



    /** @test */
    public function 前月を押下した時に表示月の前月の情報が表示される()
    {
        $user = User::factory()->create();
        $lastMonth = Carbon::now()->subMonth();

        $response = $this->actingAs($user)->get(route('attendance.list', ['month' => $lastMonth->format('Y-m')]));

        $response->assertStatus(200);
        $response->assertSee($lastMonth->format('Y/m'));
    }

    /** @test */
    public function 翌月を押下した時に表示月の翌月の情報が表示される()
    {
        $user = User::factory()->create();
        $nextMonth = Carbon::now()->addMonth();

        $response = $this->actingAs($user)->get(route('attendance.list', ['month' => $nextMonth->format('Y-m')]));

        $response->assertStatus(200);
        $response->assertSee($nextMonth->format('Y/m'));
    }

    /** @test */
    public function 詳細を押下するとその日の勤怠詳細画面に遷移する()
    {
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'clock_in' => '09:00:00'
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $detailUrl = route('attendance.detail', ['id' => $attendance->id, 'mode' => 'original']);

        $response->assertSee(e($detailUrl), false);

        // 詳細ページへアクセス
        $this->actingAs($user)->get($detailUrl)->assertStatus(200);
    }
}