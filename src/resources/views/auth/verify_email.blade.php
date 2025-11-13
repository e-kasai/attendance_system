@extends("layouts.app")

@push("styles")
    <link rel="stylesheet" href="{{ asset("css/verify_email.css") }}" />
@endpush

@section("content")
    <main class="verify">
        <div class="verify__container">
            <h1 class="verify__header">
                登録していただいたメールアドレスに認証メールを送付しました。
                <br />
                メール認証を完了してください。
            </h1>
            <a class="verify__link" href="http://localhost:8025" target="_blank" rel="noopener">認証はこちらから</a>
            <form class="verify__form" method="POST" action="{{ route("verification.send") }}">
                @csrf
                <button class="verify__resend" type="submit">認証メールを再送する</button>
            </form>
        </div>
    </main>
@endsection