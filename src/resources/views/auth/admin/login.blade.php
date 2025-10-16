@extends("layouts.app")

@push("styles")
    <link rel="stylesheet" href="{{ asset("css/form.css") }}" />
@endpush

@section("content")
    <x-form.card title="管理者ログイン" action="{{ route('login') }}" method="POST">
        <x-form.input type="email" name="email" label="メールアドレス" autocomplete="email" required />
        <x-form.input type="password" name="password" label="パスワード" required />
        <input type="hidden" name="role" value="admin" />

        <x-slot name="actions">
            <button class="btn" type="submit">管理者ログインする</button>
        </x-slot>
    </x-form.card>
@endsection
