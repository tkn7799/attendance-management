<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DateTimeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 現在の日時情報がUIと同じ形式で出力されている()
    {
        // 1. ログインユーザーの準備
        $user = User::factory()->create();

        // 2. 現在時刻を「2026年2月5日 10:00:00」に固定する
        $knownDate = Carbon::create(2026, 2, 5, 10, 0, 0);
        Carbon::setTestNow($knownDate);

        // 3. 勤怠打刻画面を開く
        $response = $this->actingAs($user)->get('/attendance');

        // 4. 期待する表示形式を定義
        // 例: 「2026年2月5日(木)」などの形式
        $expectedDate = $knownDate->isoFormat('YYYY年M月D日(ddd)');

        // 5. 画面に表示されている日時情報が一致することを確認
        $response->assertStatus(200);
        $response->assertSee($expectedDate);

        // テスト終了後は時刻固定を解除する
        Carbon::setTestNow();
    }
}
