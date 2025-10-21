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
                    {{-- 日付：06/01(木) --}}
                    <td class="index-table__cell">
                        {{ \Carbon\Carbon::parse($attendance->date)->translatedFormat("m/d(D)") }}
                    </td>
                    {{-- 出勤 --}}
                    <td class="index-table__cell">
                        {{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format("H:i") : "" }}
                    </td>

                    {{-- 退勤 --}}
                    <td class="index-table__cell">
                        {{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format("H:i") : "" }}
                    </td>

                    {{-- 休憩（分→h:mm形式） --}}
                    <td class="index-table__cell">
                        @if (! is_null($attendance->break_time))
                            @php
                                $hours = floor($attendance->break_time / 60);
                                $minutes = $attendance->break_time % 60;
                            @endphp

                            {{ sprintf("%d:%02d", $hours, $minutes) }}
                        @else
                            {{ "" }}
                        @endif
                    </td>
                    {{-- 合計（分→h:mm形式） --}}
                    <td class="index-table__cell">
                        @if (! is_null($attendance->work_time))
                            @php
                                $hours = floor($attendance->work_time / 60);
                                $minutes = $attendance->work_time % 60;
                            @endphp

                            {{ sprintf("%d:%02d", $hours, $minutes) }}
                        @else
                            {{ "" }}
                        @endif
                    </td>
                    {{-- 詳細リンク --}}
                    <td class="index-table__cell">
                        <a href="{{ route("attendance.detail", $attendance->id) }}" class="index-table__link">詳細</a>
                    </td>
                </tr>
            @endforeach
        </x-index.table>
    </x-index.container>
@endsection
