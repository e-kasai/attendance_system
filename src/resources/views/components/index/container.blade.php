<div class="index-container">
    <h1 class="index-container__title">{{ $title }}</h1>

    {{-- 検索フォーム（任意） --}}
    @if (! empty($search))
        <div class="index-container__search">
            {{ $search }}
        </div>
    @endif

    {{-- テーブル --}}
    <div class="index-container__table">
        {{ $slot }}
    </div>
</div>
