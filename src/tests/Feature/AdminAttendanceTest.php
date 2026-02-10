<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;

class AdminAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // 管理者ユーザーを作成
        $this->admin = User::factory()->create(['role' => 1]);
    }

    /**
     * @test
     * ユーザー情報と勤怠データの表示テスト
     */
    public function test_管理者ユーザーが全一般ユーザーの氏名メールアドレスと勤怠情報を確認できる()
    {
        // 一般ユーザー2名作成
        $user1 = User::factory()->create(['name' => 'ユーザー1', 'email' => 'user1@example.com']);
        $user2 = User::factory()->create(['name' => 'ユーザー2', 'email' => 'user2@example.com']);

        $today = Carbon::today()->format('Y-m-d');

        // 勤怠データ作成
        Attendance::create([
            'user_id' => $user1->id,
            'date' => $today,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00'
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.attendance.list'));

        $response->assertStatus(200);
        // 全ユーザーの氏名が正しく表示されている
        $response->assertSee('ユーザー1');
        $response->assertSee('ユーザー2');

        // 勤怠情報が正確に表示されている（09:00, 18:00）
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * @test
     * 日付移動のテスト
     */
    public function test_勤怠一覧画面で現在の日付が表示され前日翌日の移動が正しく機能する()
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $tomorrow = Carbon::tomorrow();

        // 1. 初期表示（今日）
        $response = $this->actingAs($this->admin)->get(route('admin.attendance.list'));
        $response->assertSee($today->format('Y年n月j日'));

        // 2. 「前日」を押下した時の遷移
        $responseYesterday = $this->get(route('admin.attendance.list', ['date' => $yesterday->format('Y-m-d')]));
        $responseYesterday->assertSee($yesterday->format('Y年n月j日'));

        // 3. 「翌日」を押下した時の遷移
        $responseTomorrow = $this->get(route('admin.attendance.list', ['date' => $tomorrow->format('Y-m-d')]));
        $responseTomorrow->assertSee($tomorrow->format('Y年n月j日'));
    }

/**
     * @test
     * スタッフ一覧の確認
     */
    public function 管理者がスタッフ一覧画面で全一般ユーザーの氏名とメールアドレスを確認できる()
    {
        // 一般ユーザーを複数名作成
        User::factory()->create(['name' => 'スタッフA', 'email' => 'staff_a@example.com', 'role' => 2]);
        User::factory()->create(['name' => 'スタッフB', 'email' => 'staff_b@example.com', 'role' => 2]);
        User::factory()->create(['name' => 'スタッフC', 'email' => 'staff_c@example.com', 'role' => 2]);

        // スタッフ一覧画面（admin.staff.list）へアクセス
        $response = $this->actingAs($this->admin)->get(route('admin.staff.list'));

        $response->assertStatus(200);

        // 全スタッフの氏名とメールアドレスが表示されているか
        $response->assertSee('スタッフA');
        $response->assertSee('staff_a@example.com');
        $response->assertSee('スタッフB');
        $response->assertSee('staff_b@example.com');
        $response->assertSee('スタッフC');
        $response->assertSee('staff_c@example.com');
    }

    /**
     * @test
     * スタッフ一覧から各スタッフの勤怠一覧へ遷移できる
     */
    public function スタッフ一覧画面で詳細を押下するとそのスタッフの勤怠一覧画面に遷移する()
    {
        $targetUser = User::factory()->create(['name' => '特定スタッフ', 'role' => 2]);

        $response = $this->actingAs($this->admin)->get(route('admin.staff.list'));

        // 特定スタッフの勤怠一覧（admin.attendance.staff）へのリンクが存在するか
        $userAttendanceUrl = route('admin.attendance.staff', ['id' => $targetUser->id]);
        $response->assertSee($userAttendanceUrl);

        // 実際にリンク先にアクセスして200が返るか
        $this->get($userAttendanceUrl)->assertStatus(200);
    }

    /**
     * @test
     * 月移動と詳細遷移のテスト
     */
    public function test_月移動ボタンが機能し詳細を押下すると勤怠詳細画面に遷移する()
    {
        $user = User::factory()->create();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = (clone $thisMonth)->subMonth();
        $nextMonth = (clone $thisMonth)->addMonth();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $thisMonth->format('Y-m-d'),
            'clock_in' => '09:00:00'
        ]);

        // ユーザー別勤怠一覧画面
        $url = route('admin.attendance.staff', ['id' => $user->id]);

        // 1. 前月移動
        $this->actingAs($this->admin)->get($url . '?month=' . $lastMonth->format('Y-m'))
             ->assertSee($lastMonth->format('Y/m'));

        // 2. 翌月移動
        $this->get($url . '?month=' . $nextMonth->format('Y-m'))
             ->assertSee($nextMonth->format('Y/m'));

        // 3. 詳細遷移
        $response = $this->get($url);
        $detailPath = parse_url(route('admin.attendance.detail', ['id' => $attendance->id]), PHP_URL_PATH);
        $response->assertSee($detailPath);

        $this->get($detailPath)->assertStatus(200);
    }
}