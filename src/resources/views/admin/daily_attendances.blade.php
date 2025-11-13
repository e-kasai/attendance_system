@extends("layouts.app")

@push("styles")
    <link rel="stylesheet" href="{{ asset("css/components/index.css") }}" />
@endpush

@section("content")
    <x-index.container :title="__('' . \Carbon\Carbon::parse($selectedDate)->isoFormat('YYYY年M月D日') . 'の勤怠')">
        {{-- 日付ナビ部分（calendarNavスロットを利用） --}}
        <x-slot name="calendarNav">
            <form class="date-select" method="GET" action="{{ route("admin.attendances.index") }}">
                {{-- 前日 --}}
                <button class="date-select__button" type="submit" name="target_date" value="{{ $prevDate }}">
                    <img class="select__arrow-icon" src="{{ asset("img/arrow_icon.png") }}" alt="logo" />
                    前日
                </button>

                {{-- 選択した日付 --}}
                <div class="date-select__display">
                    <img class="select__calendar-icon" src="{{ asset("img/calendar_icon.png") }}" alt="logo" />
                    <span class="month-select__text">{{ \Carbon\Carbon::parse($selectedDate)->format("Y/m/d") }}</span>
                </div>

                {{-- 翌日 --}}
                <button class="date-select__button" type="submit" name="target_date" value="{{ $nextDate }}">
                    翌日
                    <img class="select__arrow-icon next" src="{{ asset("img/arrow_icon.png") }}" alt="logo" />
                </button>
            </form>
        </x-slot>

        {{-- 勤怠一覧テーブル --}}
        <x-slot name="table">
            <x-index.table :headers="['名前', '出勤', '退勤', '休憩', '合計', '詳細']">
                @foreach ($attendances as $attendance)
                    <tr>
                        <td class="index-table__cell">{{ $attendance->user->name }}</td>
                        <td class="index-table__cell">
                            {{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format("H:i") : "" }}
                        </td>
                        <td class="index-table__cell">
                            {{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format("H:i") : "" }}
                        </td>
                        {{-- 合計（休憩） --}}
                        <td class="index-table__cell">
                            @if (! is_null($attendance->break_time))
                                @php
                                    $hours = floor($attendance->break_time / 60);
                                    $minutes = $attendance->break_time % 60;
                                @endphp

                                {{ sprintf("%02d:%02d", $hours, $minutes) }}
                            @endif
                        </td>

                        {{-- 合計（勤務時間） --}}
                        <td class="index-table__cell">
                            @if (! is_null($attendance->work_time))
                                @php
                                    $hours = floor($attendance->work_time / 60);
                                    $minutes = $attendance->work_time % 60;
                                @endphp

                                {{ sprintf("%02d:%02d", $hours, $minutes) }}
                            @endif
                        </td>
                        <td class="index-table__cell">
                            <a class="index-table__link" href="{{ route("admin.attendance.detail", $attendance->id) }}">詳細</a>
                        </td>
                    </tr>
                @endforeach
            </x-index.table>
        </x-slot>
    </x-index.container>
@endsection
