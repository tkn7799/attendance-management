<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 管理者_メールアドレスが未入力ならバリデーションメッセージが表示される()
    {
        $response = $this->post('/admin/login', [
            'email' => '',
            'password' => 'adminpass',
        ]);

        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    /** @test */
    public function 管理者_パスワードが未入力ならバリデーションメッセージが表示される()
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    /** @test */
    public function 管理者_登録内容と一致しないならバリデーションメッセージが表示される()
    {
        // 管理者ユーザーを作成
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('adminpass'),
            'role' => 1,
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'wrong-admin@example.com',
            'password' => 'adminpass',
        ]);

        $response->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
    }

    /** @test */
    public function 管理者_正しい情報でログインでき管理者用勤怠一覧に遷移する()
    {
        $admin = User::factory()->create([
            'password' => bcrypt('adminpass'),
            'role' => 1,
        ]);

        $response = $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'adminpass',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect('/admin/attendance/list');
    }
}
