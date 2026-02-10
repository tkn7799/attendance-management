<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | ステータス表示のテスト
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function 勤怠ステータスが正しく表示される()
    {
        $user = User::factory()->create();

        // 1. 勤務外
        $this->actingAs($user)->get('/attendance')->assertSee('勤務外');

        // 2. 出勤中
        $attendance = Attendance::create(['user_id' => $user->id, 'date' => Carbon::today(), 'clock_in' => '09:00']);
        $this->actingAs($user)->get('/attendance')->assertSee('出勤中');

        // 3. 休憩中
        $rest = Rest::create(['attendance_id' => $attendance->id, 'start_time' => '12:00']);
        $this->actingAs($user)->get('/attendance')->assertSee('休憩中');

        // 4. 退勤済
        $rest->update(['end_time' => '13:00']);
        $attendance->update(['clock_out' => '18:00']);
        $this->actingAs($user)->get('/attendance')->assertSee('退勤済');
    }

    /*
    |--------------------------------------------------------------------------
    | 打刻機能（出勤・退勤）のテスト
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function 出勤ボタンが正しく機能する()
    {
        $user = User::factory()->create();

        // 現在時刻を固定して打刻
        Carbon::setTestNow(Carbon::today()->setTime(9, 0, 0));

        $response = $this->actingAs($user)->post('/attendance/clock-in');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'clock_in' => '09:00:00'
        ]);
        $response->assertStatus(302);
        Carbon::setTestNow();
    }

    /** @test */
    public function 退勤ボタンが正しく機能する()
    {
        $user = User::factory()->create();
        $attendance = Attendance::create(['user_id' => $user->id, 'date' => Carbon::today(), 'clock_in' => '09:00']);

        Carbon::setTestNow(Carbon::today()->setTime(18, 0, 0));

        $this->actingAs($user)->post('/attendance/clock-out');

        $this->assertNotNull($attendance->fresh()->clock_out);
        $this->assertEquals('18:00:00', $attendance->fresh()->clock_out->format('H:i:s'));

        Carbon::setTestNow();
    }

    /** @test */
    public function 出勤は一日一回のみ可能()
    {
        $user = User::factory()->create();
        Attendance::create(['user_id' => $user->id, 'date' => Carbon::today(), 'clock_in' => '09:00', 'clock_out' => '18:00']);

        // すでに退勤済みの日は「出勤」ボタンが表示されないことを確認
        $this->actingAs($user)->get('/attendance')->assertDontSee('出勤');
    }

    /*
    |--------------------------------------------------------------------------
    | 休憩機能のテスト
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function 休憩ボタンが正しく機能する()
    {
        $user = User::factory()->create();
        $attendance = Attendance::create(['user_id' => $user->id, 'date' => Carbon::today(), 'clock_in' => '09:00']);

        $this->actingAs($user)->post('/attendance/rest-start');

        $this->assertDatabaseHas('rests', [
            'attendance_id' => $attendance->id,
            'end_time' => null
        ]);
    }

    /** @test */
    public function 休憩戻ボタンが正しく機能する()
    {
        $user = User::factory()->create();
        $attendance = Attendance::create(['user_id' => $user->id, 'date' => Carbon::today(), 'clock_in' => '09:00']);
        $rest = Rest::create(['attendance_id' => $attendance->id, 'start_time' => '12:00']);

        $this->actingAs($user)->post('/attendance/rest-end');

        $this->assertNotNull($rest->fresh()->end_time);
    }

    /** @test */
    public function 休憩および休憩戻は一日に何回でもできる()
    {
        $user = User::factory()->create();
        $attendance = Attendance::create(['user_id' => $user->id, 'date' => Carbon::today(), 'clock_in' => '09:00']);

        // 1回目の休憩完了
        Rest::create(['attendance_id' => $attendance->id, 'start_time' => '12:00', 'end_time' => '12:15']);
        // 2回目の休憩開始
        $this->actingAs($user)->post('/attendance/rest-start');

        $this->assertEquals(2, Rest::where('attendance_id', $attendance->id)->count());
    }

    /*
    |--------------------------------------------------------------------------
    | 勤怠一覧画面への反映テスト
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function 出勤・休憩・退勤時刻が勤怠一覧画面で確認できる()
    {
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => '08:30',
            'clock_out' => '18:15'
        ]);
        Rest::create(['attendance_id' => $attendance->id, 'start_time' => '12:00', 'end_time' => '13:00']);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertSee('08:30');
        $response->assertSee('18:15');
        $response->assertSee('01:00');
    }
}