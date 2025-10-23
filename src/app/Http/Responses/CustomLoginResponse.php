<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract; //Fortifyのインターフェースにaliasをつける

class CustomLoginResponse implements LoginResponseContract   //ログイン成功後に呼ばれるtoResponse()の中身を自作クラスにする
{
    public function toResponse($request)
    {
        $user = $request->user();

        //役割で分岐（admin / staff など）
        $redirectUrl = match (true) {
            $user->role === 'admin' => route('admin.attendances.index'),
            $user->role === 'staff' => route('attendance.create'),
            default                 => route('attendance.create'), // どちらにも当てはまらないときはstaffページへ
        };

        return redirect($redirectUrl);
    }
}
