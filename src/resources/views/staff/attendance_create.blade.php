@extends("layouts.app")

@push("styles")
    <link rel="stylesheet" href="{{ asset("css/attendance.css") }}" />
@endpush

@section("content")
    <h1>スタッフ用勤怠登録画面</h1>
    <section class="attendance">
        @if (session("message"))
            <p class="attendance__alert attendance__alert--success">
                {{ session("message") }}
            </p>
        @endif

        @if (session("error"))
            <p class="attendance__alert attendance__alert--error">
                {{ session("error") }}
            </p>
        @endif

        <div class="attendance__status">
            {{-- <p class="attendance__status">{{ $statusLabel[$status] ?? '不明' }}</p> --}}
        </div>

        <div class="attendance__current">
            <p class="attendance__date" id="today">{{ $today }}</p>
            <p class="attendance__time" id="clock">{{ $time }}</p>
        </div>

        {{-- 状態によってボタンを切り替え --}}
        <div class="attendance__actions">
            @switch($status)
                @case("off_duty")
                    {{-- 出勤ボタン --}}
                    <form action="{{ route("attendance.store") }}" method="POST">
                        @csrf
                        <input type="hidden" name="action" value="work_start" />
                        <button type="submit" class="btn btn--work_start" formnovalidate>出勤</button>
                    </form>

                    @break
                @case("working")
                    {{-- 退勤・休憩入ボタン --}}
                    <form action="{{ route("attendance.store") }}" method="POST">
                        @csrf
                        <input type="hidden" name="action" value="work_end" />
                        <button type="submit" class="btn btn--work_end" formnovalidate>退勤</button>
                    </form>
                    <form action="{{ route("attendance.store") }}" method="POST">
                        @csrf
                        <input type="hidden" name="action" value="break_start" />
                        <button type="submit" class="btn btn--break_start" formnovalidate>休憩入</button>
                    </form>

                    @break
                @case("on_break")
                    {{-- 休憩戻ボタン --}}
                    <form action="{{ route("attendance.store") }}" method="POST">
                        @csrf
                        <input type="hidden" name="action" value="break_end" />
                        <button type="submit" class="btn btn--break_end" formnovalidate>休憩戻</button>
                    </form>

                    @break
                @case("finished")
                    {{-- お疲れさまでしたメッセージ --}}
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
