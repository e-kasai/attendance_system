@extends("layouts.app")

@push("styles")
    <link rel="stylesheet" href="{{ asset("css/components/form.css") }}" />
@endpush

@section("content")
    <x-form.card title="ログイン" action="{{ route('login') }}" method="POST">
        <x-form.input type="email" name="email" label="メールアドレス" autocomplete="email" required />
        <x-form.input type="password" name="password" label="パスワード" required />

        <x-slot name="actions">
            <button class="btn" type="submit">ログインする</button>
            <a class="link" href="{{ route("register.show") }}">会員登録はこちら</a>
        </x-slot>
    </x-form.card>
@endsection
