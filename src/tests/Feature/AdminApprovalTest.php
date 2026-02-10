<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AdminApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 1]);
    }

    /**
     * 1. 詳細画面の表示確認
     */
    public function test_管理者ユーザーが勤怠詳細ページを開くと選択した情報が表示される()
    {
        $user = User::factory()->create(['role' => 0]);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-02-05',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00'
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.attendance.detail', ['id' => $attendance->id]));

        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * 2. バリデーションテスト（管理者の直接修正）
     */
    public function test_管理者の修正で出勤が退勤より後の場合エラーが表示される()
    {
        $attendance = Attendance::create(['user_id' => User::factory()->create()->id, 'date' => '2026-02-05']);

        $response = $this->actingAs($this->admin)->post(route('admin.attendance.update', ['id' => $attendance->id]), [
            'clock_in' => '18:00',
            'clock_out' => '09:00',
            'remarks' => '修正'
        ]);

        $response->assertSessionHasErrors(['clock_out' => '出勤時間もしくは退勤時間が不適切な値です']);
    }

    public function test_管理者の修正で休憩開始が退勤より後の場合エラーが表示される()
    {
        $attendance = Attendance::create(['user_id' => User::factory()->create()->id, 'date' => '2026-02-05']);

        $response = $this->actingAs($this->admin)->post(route('admin.attendance.update', ['id' => $attendance->id]), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'rests' => [['start' => '19:00', 'end' => '17:00']],
            'remarks' => '修正'
        ]);

        $response->assertSessionHasErrors(['rests.0.end' => '休憩時間が不適切な値です']);
    }

    public function test_管理者の修正で休憩終了が退勤より後の場合エラーが表示される()
    {
        $attendance = Attendance::create(['user_id' => User::factory()->create()->id, 'date' => '2026-02-05']);

        $response = $this->actingAs($this->admin)->post(route('admin.attendance.update', ['id' => $attendance->id]), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'rests' => [['start' => '12:00', 'end' => '19:00']],
            'remarks' => '修正'
        ]);

        $response->assertSessionHasErrors(['rests.0.end' => '休憩時間もしくは退勤時間が不適切な値です']);
    }

    public function test_管理者修正で備考未入力の場合エラーが表示される()
    {
        $attendance = Attendance::create(['user_id' => User::factory()->create()->id, 'date' => '2026-02-05']);

        $response = $this->actingAs($this->admin)->post(route('admin.attendance.update', ['id' => $attendance->id]), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'remarks' => ''
        ]);

        $response->assertSessionHasErrors(['remarks' => '備考を記入してください']);
    }

    /**
     * 3. 修正申請一覧のテスト（タブ切り替え）
     */
    public function test_修正申請一覧ページで承認待ちと承認済みのデータが正しく表示される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-02-05',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00'
        ]);
        // 承認待ちデータ
        AttendanceCorrection::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'revised_clock_in' => '09:00:00',
            'revised_clock_out' => '18:00:00',
            'remarks' => '承認待ちの申請',
            'status' => 0
        ]);

        // 承認済みデータ
        AttendanceCorrection::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'revised_clock_in' => '10:00:00',
            'revised_clock_out' => '19:00:00',
            'remarks' => '承認済みの申請',
            'status' => 1
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.correction.list', ['tab' => 'pending']));
        $response->assertStatus(200);
        $response->assertSee('承認待ちの申請');
        $response->assertDontSee('承認済みの申請');

        $responseApproved = $this->get(route('admin.correction.list', ['tab' => 'approved']));
        $responseApproved->assertStatus(200);
        $responseApproved->assertSee('承認済みの申請');
        $responseApproved->assertDontSee('承認待ちの申請');
    }

    /**
     * 4. 承認処理のテスト
     */
    public function test_修正申請の詳細画面から承認処理が正しく行われる()
    {
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-02-05',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00'
        ]);

        $correction = AttendanceCorrection::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'revised_clock_in' => '10:00:00',
            'revised_clock_out' => '19:00:00',
            'remarks' => '申請理由',
            'status' => 0
        ]);

        // 承認処理
        $response = $this->actingAs($this->admin)->post(route('admin.correction.approve', ['attendance_correct_request_id' => $correction->id]));

        // 1. 修正申請のステータスが更新されているか
        $this->assertEquals(1, $correction->fresh()->status);

        // 2. 元の勤怠データが書き換わっているか
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00'
        ]);
    }
}
