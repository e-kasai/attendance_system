@extends("layouts.app")

@push("styles")
    <link rel="stylesheet" href="{{ asset("css/components/index.css") }}" />
    <link rel="stylesheet" href="{{ asset("css/components/detail_table.css") }}" />
@endpush

@section("content")
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        // 名前、日付、出退勤行
        $rows = [
            ["label" => "名前", "value" => $user->name],
            ["label" => "日付", "value" => $attendance->date->format("Y年n月j日")],
            [
                "label" => "出勤・退勤",
                "type" => "time-range",
                "name" => ["clock_in", "clock_out"],
                "value" => [$attendance->clock_in?->format("H:i"), $attendance->clock_out?->format("H:i")],
            ],
        ];

        foreach ($allBreaks as $index => $break) {
            $rows[] = [
                "label" => $index === 0 ? "休憩" : "休憩" . ($index + 1),
                "type" => "time-range",
                "name" => ["breaks[$index][break_in]", "breaks[$index][break_out]"],
                "value" => [
                    $break->break_in ? \Carbon\Carbon::parse($break->break_in)->format("H:i") : null,
                    $break->break_out ? \Carbon\Carbon::parse($break->break_out)->format("H:i") : null,
                ],
            ];
        }
        // 備考
        $rows[] = ["label" => "備考", "name" => "comment", "type" => "textarea", "value" => $update->comment ?? ""];
    @endphp

    {{-- テーブル全体を1回で描画 --}}
    <form method="POST" action="{{ route("admin.request.approve.update", $update->id) }}">
        @csrf
        <input type="hidden" name="attendance_id" value="{{ $attendance->id }}" />
        @method("PATCH")

        <x-index.container title="勤怠詳細">
            <x-detail.table :rows="$rows" :isEditable="$isEditable" :btnActivate="$btnActivate" />
            @if ($btnActivate)
                <button type="submit" class="detail-table__btn">承認</button>
            @else
                <button type="submit" class="detail-table__btn" disabled>承認済み</button>
            @endif
        </x-index.container>
    </form>
    {{-- 修正不可メッセージ --}}
    @if ($message)
        <p class="detail-table__notice">{{ $message }}</p>
    @endif
@endsection
