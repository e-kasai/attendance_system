@extends("layouts.app")

@push("styles")
    <link rel="stylesheet" href="{{ asset("css/list.css") }}" />
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

        // 休憩行を動的に追加（複数休憩対応）
        foreach ($attendance->breakTimes as $index => $break) {
            $rows[] = [
                "label" => $index === 0 ? "休憩" : "休憩" . ($index + 1),
                "type" => "time-range",
                "name" => ["breaks[$index][break_in]", "breaks[$index][break_out]"],
                "value" => [$break->break_in?->format("H:i"), $break->break_out?->format("H:i")],
            ];
        }
        // 備考
        $rows[] = ["label" => "備考", "name" => "comment", "type" => "textarea", "value" => $update->comment ?? ""];
    @endphp

    {{-- テーブル全体を1回で描画 --}}
    <form
        method="POST"
        action="{{
            auth()->user()->role === "admin"
                ? route("admin.attendances.update", $attendance->id)
                : route("attendance.update", $attendance->id)
        }}"
    >
        @csrf
        <input type="hidden" name="attendance_id" value="{{ $attendance->id }}" />
        @method("PATCH")

        <x-index.container title="勤怠詳細">
            <x-detail.table :rows="$rows" isEditable="true" />
            <button type="submit" class="detail-table__btn">修正</button>
        </x-index.container>
    </form>
@endsection
