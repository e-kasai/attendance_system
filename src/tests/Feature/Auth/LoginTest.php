<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_login_fails_if_email_is_empty()
    {
        //メールアドレスを入れずにログイン
        $response = $this->post(route('login'), [
            'email' => '',
            'password' => 'password123',
        ]);
        // 期待：メールアドレスを入力してくださいというエラーメッセージが返る
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    public function test_login_fails_if_password_is_empty()
    {
        //パスワードを入れずにログイン
        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => '',
        ]);
        // 期待：パスワードを入力してくださいというエラーメッセージが返る
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    public function test_login_fails_if_email_is_not_registered()
    {
        $response = $this->post(route('login'), [
            //メールアドレスは未登録想定（RefreshDatabaseしてるのでそもそも登録済みアドレスがないため）
            'email' => 'no-such-user@example.com',
            'password' => 'password',
        ]);

        //期待：ログイン情報が登録されていませんというエラーメッセージが返る
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }
}
