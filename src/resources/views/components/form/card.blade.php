{{-- フォーム全体の共通レイアウト --}}

@props(["title" => "", "action" => "#", "method" => "POST", "enctype" => ""])

<section class="form-card">
    <h1 class="form-card__title">{{ $title }}</h1>

    <form class="form-card__contents" method="POST" action="{{ $action }}" enctype="{{ $enctype }}" novalidate>
        @csrf
        {{-- strtoupper は PHPの組み込み関数、文字列をすべて大文字に変換 --}}
        @if (strtoupper($method) !== "POST")
            {{-- @methodでPOST以外の場合にPUT や PATCH、DELETE などのHTTPメソッドを擬似的に送信 --}}
            @method($method)
        @endif

        <div class="form-card__inputs">
            {{ $slot }}
        </div>

        <div class="form-card__actions">
            {{ $actions ?? "" }}
        </div>
    </form>
</section>
