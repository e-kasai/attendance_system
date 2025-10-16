<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Requests\LoginRequest; //fortifyの認証ロジック
use App\Http\Requests\Auth\LoginUserRequest;    //自作のFormRequest
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Fortifyが使う LoginRequest を自作のFR：UserLoginRequestに差し替える
        $this->app->bind(LoginRequest::class, LoginUserRequest::class);

        Fortify::authenticateUsing(function ($request) {
            $user = User::where('email', $request->email)->first();

            if ($user && Hash::check($request->password, $user->password)) {
                if ($request->role === 'admin' && !$user->is_admin) {
                    throw ValidationException::withMessages(
                        [
                            'email' => ['管理者アカウントではありません。'],
                        ]
                    );
                }
                if ($request->role !== 'admin' && $user->is_admin) {
                    throw ValidationException::withMessages(
                        [
                            'email' => ['スタッフアカウントではありません。'],
                        ]
                    );
                }
                return $user;
            }

            return null; // 認証失敗
        });

        //スタッフのログインページを表示
        Fortify::loginView(fn() => view('auth.login'));

        //同じIPや同じメールからの連続ログイン施行を制限
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())) . '|' . $request->ip());
            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
