@extends("layouts.app")

@push("styles")
    <link rel="stylesheet" href="{{ asset("css/form.css") }}" />
@endpush

@section("content")
    <h1>スタッフ用勤怠登録画面</h1>
    <div class="attendance">
        {{-- <p class="attendance__status">{{ $statusLabel[$status] ?? '不明' }}</p> --}}
        <p id="today">{{ $today }}</p>
        <p id="clock" class="attendance__time">{{ $time }}</p>

        {{-- 状態によってボタンを切り替え --}}
        {{--
            @if ($status === 'off_duty')
            <form action="{{ route('attendance.start') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn--primary">出勤</button>
            </form>
            @elseif ($status === 'working')
            <form action="{{ route('attendance.end') }}" method="POST">@csrf<button>退勤</button></form>
            <form action="{{ route('break.start') }}" method="POST">@csrf<button>休憩入</button></form>
            @elseif ($status === 'break')
            <form action="{{ route('break.end') }}" method="POST">@csrf<button>休憩戻</button></form>
            @elseif ($status === 'finished')
            <p class="attendance__message">お疲れ様でした。</p>
            @endif
        --}}
    </div>

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
@endsection
