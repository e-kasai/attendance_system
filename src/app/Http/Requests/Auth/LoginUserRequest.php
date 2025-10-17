<?php

namespace App\Http\Requests\Auth;

use Laravel\Fortify\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class LoginUserRequest extends LoginRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'メールアドレスを入力してください',
            'email.email' => 'メールアドレスはメール形式で入力してください',
            'email.max' => 'メールアドレスは255文字以下で入力してください',
            'password.required' => 'パスワードを入力してください',
            'password.string' => 'パスワードは文字列で入力してください',
            'password.min' => 'パスワードは8文字以上で入力してください',
            'password.max' => 'パスワードは255文字以下で入力してください',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // 既存ルールでfailした場合は以下の照合はしない
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $user = User::where('email', $this->input('email'))->first();
            $userExists = $user && Hash::check($this->input('password'), $user->password);

            if (! $userExists) {
                $validator->errors()->add('email', 'ログイン情報が登録されていません');
            }
        });
    }
}
