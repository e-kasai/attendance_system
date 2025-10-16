<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Actions\Fortify\CreateNewUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Auth\Events\Registered;


class RegisterController extends Controller
{
    public function showRegistrationForm(): View
    {
        return view('auth.register');
    }

    // validationは自作FRを使い、登録ロジックはFortifyのCreateNewUserを再利用
    public function store(RegisterUserRequest $request, CreateNewUser $creator): RedirectResponse
    {
        $input = $request->validated();
        $user = $creator->create($input);

        event(new Registered($user));

        auth()->login($user);
        $request->session()->regenerate();
        return redirect()->route('verification.notice');
    }
}
