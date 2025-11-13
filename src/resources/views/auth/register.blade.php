@extends("layouts.app")

@push("styles")
    <link rel="stylesheet" href="{{ asset("css/components/form.css") }}" />
@endpush

@section("content")
    <x-form.card title="会員登録" action="{{ route('register.store') }}" method="POST">
        <x-form.input type="text" name="name" label="名前" autocomplete="name" required />
        <x-form.input type="email" name="email" label="メールアドレス" autocomplete="email" required />
        <x-form.input type="password" name="password" label="パスワード" required />
        <x-form.input type="password" name="password_confirmation" label="パスワード確認" required />

        <x-slot name="actions">
            <button class="btn" type="submit">登録する</button>
            <a class="link" href="{{ route("login") }}">ログインはこちら</a>
        </x-slot>
    </x-form.card>
@endsection
