@extends("layouts.app")

@push("styles")
    <link rel="stylesheet" href="{{ asset("css/list.css") }}" />
@endpush

@section("content")
    <x-index.container title="勤怠一覧">
        <x-slot name="monthNav">
            <form class="month-select" method="GET" action="{{ route("attendances.index") }}">
                <button class="month-select__button" type="submit" name="target_ym" value="{{ $prevMonth }}">← 前月</button>
                <div class="month-select__display">
                    <i class="month-select__icon fa-solid fa-calendar"></i>
                    <span class="month-select__text">{{ $selectedMonth }}</span>
                </div>
                <button class="month-select__button" type="submit" name="target_ym" value="{{ $nextMonth }}">翌月 →</button>
            </form>
        </x-slot>

        <x-index.table :headers="['日付', '出勤', '退勤', '休憩', '合計', '詳細']">
            @foreach ($attendances as $attendance)
                <tr class="index-table__row">
                    <td class="index-table__cell">{{ $attendance->date }}</td>
                    <td class="index-table__cell">{{ $attendance->clock_in }}</td>
                    <td class="index-table__cell">{{ $attendance->clock_out }}</td>
                    <td class="index-table__cell">{{ $attendance->break_time }}</td>
                    <td class="index-table__cell">{{ $attendance->work_time }}</td>
                    <td class="index-table__cell">
                        <a href="{{ route("attendance.detail", $attendance->id) }}" class="index-table__link">詳細</a>
                    </td>
                </tr>
            @endforeach
        </x-index.table>
    </x-index.container>
@endsection
