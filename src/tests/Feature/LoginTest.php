<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     * @test
     */
    public function test_メールアドレスが未入力の場合バリデーションメッセージが表示される()
    {
        // 1. ユーザーを登録する
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        // 2. メールアドレス以外のユーザー情報を入力し、3. ログインの処理を行う
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        // 「メールアドレスを入力してください」というバリデーションメッセージが表示されることを確認
        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    /**
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     * @test
     */
    public function test_パスワードが未入力の場合バリデーションメッセージが表示される()
    {
        // 1. ユーザーを登録する
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        // 2. パスワード以外のユーザー情報を入力し、3. ログインの処理を行う
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        // 「パスワードを入力してください」というバリデーションメッセージが表示されることを確認
        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    /**
     * 登録内容と一致しない場合、バリデーションメッセージが表示される
     * @test
     */
    public function test_登録内容と一致しない場合バリデーションメッセージが表示される()
    {
        // 1. ユーザーを登録する
        $user = User::factory()->create([
            'email' => 'correct@example.com',
            'password' => bcrypt('password123'),
        ]);

        // 2. 誤ったメールアドレスのユーザー情報を入力し、3. ログインの処理を行う
        $response = $this->post('/login', [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ]);

        // 「ログイン情報が登録されていません」というバリデーションメッセージが表示されることを確認
        $response->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
    }

    /**
     * 正しい情報でログインができる（期待挙動の確認）
     * @test
     */
    public function test_正しい情報でログインができる()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // 認証されていることを確認
        $this->assertAuthenticatedAs($user);

        // 勤怠画面（/attendance）へ遷移することを確認
        $response->assertRedirect('/attendance');
    }
}