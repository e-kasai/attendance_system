@extends("layouts.app")

@push("styles")
    <link rel="stylesheet" href="{{ asset("css/components/detail_table.css") }}" />
@endpush

@section("content")
    @php
        // 名前、日付、出退勤行
        $rows = [
            ["label" => "名前", "value" => $attendance->user->name, "class" => "name"],
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
                "id" => $break->id,
            ];
        }
        // 休憩の予備行(編集可能時のみ作成)
        if ($isEditable) {
            $nextIndex = $attendance->breakTimes->count();
            $rows[] = [
                "label" => "休憩" . ($nextIndex + 1),
                "type" => "time-range",
                "name" => ["breaks[$nextIndex][break_in]", "breaks[$nextIndex][break_out]"],
                "value" => ["", ""], // 空欄で新しい入力行
            ];
        }

        // 備考
        $rows[] = ["label" => "備考", "name" => "comment", "type" => "textarea", "value" => $update->comment ?? ($attendance->comment ?? "")];
    @endphp

    {{-- テーブル全体を1回で描画 --}}
    <form
        method="POST"
        action="{{
            auth()->user()->role === "admin"
                ? route("admin.attendance.update", $attendance->id)
                : route("attendance.update", $attendance->id)
        }}"
    >
        @csrf
        <input type="hidden" name="attendance_id" value="{{ $attendance->id }}" />
        @method("PATCH")

        <x-index.container title="勤怠詳細">
            <x-detail.table :rows="$rows" :isEditable="$isEditable" />

            @if ($isEditable)
                <x-slot name="btn">
                    <button class="detail-table__btn btn--modify" type="submit">修正</button>
                </x-slot>
            @endif
        </x-index.container>
    </form>

    {{-- 修正不可メッセージ --}}
    @if ($message)
        <p class="detail-table__notice">{{ $message }}</p>
    @endif
@endsection
