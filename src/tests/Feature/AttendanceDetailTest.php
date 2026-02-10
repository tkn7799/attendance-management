<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\AttendanceCorrection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

/** @test */
    public function 勤怠詳細画面にログインユーザーの打刻データが正しく表示される()
    {
        $user = User::factory()->create(['name' => 'テスト太郎']);

        // 1. 比較用のデータを作成
        $targetDate = '2026-02-05';
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $targetDate,
            'clock_in' => '09:15:00',
            'clock_out' => '18:30:00'
        ]);

        // 休憩データも作成
        Rest::create([
            'attendance_id' => $attendance->id,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00'
        ]);

        // 2. 詳細画面を表示
        $response = $this->actingAs($user)->get(route('attendance.detail', ['id' => $attendance->id, 'mode' => 'original']));

        $response->assertStatus(200);

        // 名前が表示されているか
        $response->assertSee('テスト太郎');

        // 日付が表示されているか
        $response->assertSee('2026年');
        $response->assertSee('2月5日');

        // 「出勤・退勤」の時間が一致しているか
        $response->assertSee('09:15');
        $response->assertSee('18:30');

        // 「休憩」の時間が一致しているか
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }

    /** @test */
    public function 勤怠データがない日の詳細画面は日付が正しく表示され時刻は空である()
    {
        $user = User::factory()->create();
        $targetDate = '2026-02-10';

        // 勤怠データがない状態で日付指定のURLにアクセス
        $response = $this->actingAs($user)->get(route('attendance.detail', ['date' => $targetDate, 'mode' => 'original']));

        $response->assertStatus(200);

        // 日付は表示されているか
        $response->assertSee($targetDate);

        // 時刻入力欄が空であることを確認
        $response->assertDontSee('09:00');
    }

    /*
    |--------------------------------------------------------------------------
    | バリデーションテスト
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function 出勤時間が退勤時間より後の場合_エラーが表示される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-02-05',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00'
        ]);

        $response = $this->actingAs($user)->post(route('attendance.update', ['id' => $attendance->id]), [
            'clock_in' => '20:00', // 退勤より後
            'clock_out' => '18:00',
            'remarks' => '修正理由'
        ]);

        $response->assertSessionHasErrors(['clock_out' => '出勤時間が不適切な値です']);
    }

    /** @test */
    public function 休憩開始時間が退勤時間より後の場合_エラーが表示される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-02-05',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00'
        ]);

        $response = $this->actingAs($user)->post(route('attendance.update', ['id' => $attendance->id]), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'rests' => [
                ['start' => '19:00', 'end' => '17:00'] // 退勤より後
            ],
            'remarks' => '修正理由'
        ]);

        $response->assertSessionHasErrors(['rests.0.end' => '休憩時間が不適切な値です']);
    }

    /** @test */
    public function 休憩終了時間が退勤時間より後の場合_エラーが表示される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-02-05',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00'
        ]);

        $response = $this->actingAs($user)->post(route('attendance.update', ['id' => $attendance->id]), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'rests' => [
                ['start' => '12:00', 'end' => '19:00'] // 退勤より後
            ],
            'remarks' => '修正理由'
        ]);

        $response->assertSessionHasErrors(['rests.0.end' => '休憩時間もしくは退勤時間が不適切な値です']);
    }

    /** @test */
    public function 備考欄が未入力の場合_エラーが表示される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::create(['user_id' => $user->id, 'date' => '2026-02-05', 'clock_in' => '09:00:00']);

        $response = $this->actingAs($user)->post(route('attendance.update', ['id' => $attendance->id]), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'remarks' => '' // 空
        ]);

        $response->assertSessionHasErrors(['remarks' => '備考を記入してください']);
    }

    /*
    |--------------------------------------------------------------------------
    | 申請フロー・一覧表示のテスト
    |--------------------------------------------------------------------------
    */

/** @test */
    public function 修正申請が実行され_各画面に反映される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-02-05',
            'clock_in' => '09:00:00'
        ]);

        $this->actingAs($user)->post(route('attendance.update', ['id' => $attendance->id]), [
            'clock_in' => '09:30',
            'clock_out' => '18:30',
            'remarks' => '申請テスト文言'
        ]);

        $this->assertDatabaseHas('attendance_corrections', [
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'revised_clock_in' => '09:30:00',
            'revised_clock_out' => '18:30:00',
            'remarks' => '申請テスト文言',
            'status' => 0 // 未承認
        ]);

        $response = $this->get(route('attendance.application', ['tab' => 'pending']));
        $response->assertSee('申請テスト文言');
    }

    /** @test */
    public function 承認済みに管理者が承認した修正申請が全て表示されている()
    {
        $user = User::factory()->create();
        $attendance = Attendance::create(['user_id' => $user->id, 'date' => '2026-02-05']);

        // 承認済みデータ(status: 1)を作成
        AttendanceCorrection::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'revised_clock_in' => '09:00:00',
            'revised_clock_out' => '18:00:00',
            'remarks' => '承認済みデータ',
            'status' => 1, // 承認済み
        ]);

        $response = $this->actingAs($user)->get(route('attendance.application', ['tab' => 'approved']));
        $response->assertSee('承認済みデータ');
    }

    /** @test */
    public function 各申請の詳細を押下すると勤怠詳細画面に遷移する()
    {
        $user = User::factory()->create();
        $attendance = Attendance::create(['user_id' => $user->id, 'date' => '2026-02-05']);

        AttendanceCorrection::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'revised_clock_in' => '09:00:00',
            'revised_clock_out' => '18:00:00',
            'remarks' => '詳細遷移テスト',
            'status' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.application'));

        $detailUrl = route('attendance.detail', ['id' => $attendance->id]);
        $response->assertSee($detailUrl);

        $this->get($detailUrl)->assertStatus(200);
    }
}