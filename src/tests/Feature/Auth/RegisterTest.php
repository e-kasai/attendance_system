<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RegisterTest extends TestCase
{

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_register_fails_if_name_is_empty()
    {
        //名前未入力で会員登録
        $response = $this->post(route('register.store'), [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // 期待：お名前を入力してくださいというエラーメッセージが返る
        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
        ]);
    }


    public function test_register_fails_if_email_is_empty()
    {
        //メールアドレス未入力で会員登録
        $response = $this->post(route('register.store'), [
            'name' => 'testuser',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);


        // 期待：メールアドレスを入力してくださいというエラーメッセージが返る
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }


    public function test_register_fails_if_password_is_empty()
    {
        //パスワード未入力で会員登録
        $response = $this->post(route('register.store'), [
            'name' => 'testuser',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => 'password123',
        ]);

        // 期待：パスワードを入力してくださいというエラーメッセージが返る
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }


    public function test_register_fails_if_password_is_less_than_8characters()
    {
        //パスワードを8文字未満(7文字)で会員登録
        $response = $this->post(route('register.store'), [
            'name' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'passwor',
            'password_confirmation' => 'passwor',
        ]);

        // 期待：パスワードは8文字以上で入力してくださいというエラーメッセージが返る
        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);
    }


    public function test_register_fails_if_password_confirmation_is_different_as_password()
    {
        //パスワードと異なる確認用パスワードで会員登録
        $response = $this->post(route('register.store'), [
            'name' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password',
        ]);

        // 期待：パスワードと一致しませんというエラーメッセージが返る
        $response->assertSessionHasErrors([
            'password' => 'パスワードと一致しません',
        ]);
    }

    public function test_register_succeeds_if_have_all_mandatory_info()
    {
        // emailのunique制約を回避
        $email = 'test' . uniqid() . '@example.com';

        // すべての項目が入力されている
        $response = $this->post(route('register.store'), [
            'name' => 'testuser',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);


        //期待1: ユーザーの名前、メールアドレスがDBに登録される
        $this->assertDatabaseHas('users', [
            'name' => 'testuser',
            'email' => $email,
        ]);

        //ユーザーをemailから特定
        $user = User::where('email', $email)->first();

        //期待2: ハッシュ化されたパスワードは元の文字列と一致 = ユーザーのパスワードがDBに登録されている
        $this->assertTrue(Hash::check('password123', $user->password));

        //期待3: 登録したこのユーザーは認証済みで登録が成功している
        $this->assertAuthenticatedAs($user);

        //期待4:会員登録後メール認証誘導画面に遷移する（応用機能実装済みの為遷移先はこちら）
        $response->assertRedirect(route('verification.notice'));
    }
}
