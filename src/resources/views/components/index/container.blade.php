<div class="index-container">
    <h1 class="index-container__title">{{ $title }}</h1>

    {{-- 月選択 --}}
    @if (! empty($monthNav))
        <div class="index-container__month">
            {{ $monthNav }}
        </div>
    @endif

    {{-- テーブル --}}
    <div class="index-container__table">
        {{ $table ?? $slot }}
    </div>

    {{-- ボタン --}}
    <div class="index-container__btn">
        {{ $btn ?? "" }}
    </div>
</div>
