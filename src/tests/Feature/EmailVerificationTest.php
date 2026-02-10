<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // 各テストの前にMailHogのメールを空にする
        Http::delete('http://mailhog:8025/api/v1/messages');
    }

    /**
     * 1. 会員登録後、認証メールが送信される
     */
    public function test_会員登録後に認証メールが送信される()
    {
        // 会員登録実行
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        if ($response->status() !== 302) {
            dump($response->getContent());
        }
        sleep(1);

        // 登録したメールアドレス宛にメールが届いているかMailHogで確認
        $mailhogResponse = Http::get('http://mailhog:8025/api/v1/messages');
        $messages = $mailhogResponse->json();

        $this->assertNotEmpty($messages, '認証メールが送信されていません');
        $this->assertEquals('test@example.com', $messages[0]['Content']['Headers']['To'][0]);
    }

    /**
     * 2. メール認証誘導画面で「認証はこちらから」ボタン（認証URL）から遷移する
     * 3. メール認証を完了すると勤怠登録画面に遷移する
     */
public function test_メール認証を完了すると勤怠登録画面に遷移する()
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $this->actingAs($user);

        $user->sendEmailVerificationNotification();
        sleep(1);

        $mailhogResponse = Http::get('http://mailhog:8025/api/v1/messages');
        $messages = $mailhogResponse->json();
        $this->assertNotEmpty($messages, 'MailHogにメールが届いていません。');

        // 1. 本文を取得し、エンコーディングをデコード
        $body = $messages[0]['Content']['Body'];
        $cleanBody = quoted_printable_decode($body);

        // 2. 「/email/verify/」が含まれるURLだけを抽出する
        if (preg_match('/(https?:\/\/[^\s\'">]+\/email\/verify\/[^\s\'">]+)/', $cleanBody, $matches)) {
            $fullUrl = rtrim($matches[1], '.,;:)');
            $parsedUrl = parse_url($fullUrl);

            if (!isset($parsedUrl['path'])) {
                $this->fail('パスを解析できませんでした。URL: ' . $fullUrl);
            }

            $path = $parsedUrl['path'];
            if (isset($parsedUrl['query'])) {
                $path .= '?' . $parsedUrl['query'];
            }

            // 3. 認証実行
            $response = $this->get($path);

            // 4. 検証
            $response->assertRedirect(route('attendance.index') . '?verified=1');
            $this->assertNotNull($user->fresh()->email_verified_at);
        } else {
            // 失敗した場合、何が届いていたかを表示してデバッグしやすくする
            dump("抽出失敗。本文の一部:", substr($cleanBody, 0, 500));
            $this->fail('メール本文から認証用URL（/email/verify/...）が見つかりませんでした。');
        }
    }
}