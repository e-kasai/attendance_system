<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;

class EmailVerifyTest extends TestCase
{
    use RefreshDatabase;

    public function test_verification_email_is_sent_to_registered_address()
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect();

        $user = User::whereEmail('test@example.com')->firstOrFail();

        Notification::assertSentTo(
            $user,
            VerifyEmail::class
        );
    }

    public function test_notice_page_has_link_to_mailhog()
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('href="http://localhost:8025"', false);
    }

    public function test_email_verification_redirects_to_items_index()
    {
        $user = User::factory()->unverified()->create();

        //認証用署名URLを生成
        $verifyUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verifyUrl);
        $response->assertRedirect(route('attendance.create'));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}
