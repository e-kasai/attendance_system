<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract; //Fortifyのインターフェースにaliasをつける

class CustomLoginResponse implements LoginResponseContract   //ログイン成功後に呼ばれるtoResponse()の中身を自作クラスにする
{
    public function toResponse($request)
    {
        $user = $request->user();

        return redirect()->intended(
            $user->is_admin
                ? route('admin.attendances.index') //ログイン先を分岐
                : route('attendance.create')
        );
    }
}
