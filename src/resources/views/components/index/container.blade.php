<div class="index-container">
    <h1 class="index-container__title">{{ $title }}</h1>

    {{-- 月日選択 --}}
    @if (! empty($calendarNav))
        <div class="index-container__calendar">
            {{ $calendarNav }}
        </div>
    @endif

    {{-- 切り替えタブ --}}
    {{ $tabs ?? "" }}

    {{-- テーブル --}}
    <div class="index-container__table">
        {{ $table ?? $slot }}
    </div>

    {{-- ボタン --}}
    @if (! empty($btn))
        <div class="container__btn-area">
            {{ $btn ?? "" }}
        </div>
    @endif
</div>
