@extends("layouts.app")

@push("styles")
    <link rel="stylesheet" href="{{ asset("css/attendance_create.css") }}" />
@endpush

@section("content")
    <section class="attendance">
        <div class="attendance__status">
            <p class="attendance__status-text">{{ $statusLabel[$status] ?? "不明" }}</p>
        </div>

        <div class="attendance__current">
            <p class="attendance__date" id="today">{{ $today }}</p>
            <p class="attendance__time js-clock" id="clock">{{ $time }}</p>
        </div>

        {{-- 状態によってボタンを切り替え --}}
        <div class="attendance__actions">
            @switch($status)
                @case(1)
                    {{-- 出勤ボタン --}}
                    <form action="{{ route("attendance.store") }}" method="POST">
                        @csrf
                        <input type="hidden" name="action" value="work_start" />
                        <button type="submit" class="attendance__btn attendance__btn--primary" formnovalidate>出勤</button>
                    </form>

                    @break
                @case(2)
                    {{-- 退勤・休憩入ボタン --}}
                    <form action="{{ route("attendance.store") }}" method="POST">
                        @csrf
                        <input type="hidden" name="action" value="work_end" />
                        <button type="submit" class="attendance__btn attendance__btn--primary" formnovalidate>退勤</button>
                    </form>
                    <form action="{{ route("attendance.store") }}" method="POST">
                        @csrf
                        <input type="hidden" name="action" value="break_in" />
                        <button type="submit" class="attendance__btn attendance__btn--secondary" formnovalidate>休憩入</button>
                    </form>

                    @break
                @case(3)
                    {{-- 休憩戻ボタン --}}
                    <form action="{{ route("attendance.store") }}" method="POST">
                        @csrf
                        <input type="hidden" name="action" value="break_out" />
                        <button type="submit" class="attendance__btn attendance__btn--secondary" formnovalidate>休憩戻</button>
                    </form>

                    @break
                @case(4)
                    <p class="attendance__message">お疲れ様でした。</p>

                    @break
            @endswitch
        </div>
    </section>
@endsection

@push("scripts")
    <script>
        setInterval(() => {
            const now = new Date();

            // 時と分を2桁表示に整える
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');

            document.getElementById('clock').textContent = `${hours}:${minutes}`; // 1秒ごとに時刻を更新
        }, 1000);
    </script>
@endpush
