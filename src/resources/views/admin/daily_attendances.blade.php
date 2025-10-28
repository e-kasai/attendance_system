@extends("layouts.app")

@section("content")
    <x-index.container :title="__('日次勤怠一覧')">
        {{-- 日付ナビ部分（monthNavスロットを利用） --}}
        <x-slot name="monthNav">
            {{--
                <form class="date-select" method="GET" action="{{ route("admin.attendances.index") }}">
                <button class="date-select__button" type="submit" name="target_date" value="{{ now()->format('Y-m-d') }}">今日</button>
                <button class="date-select__button" type="submit" name="target_date" value="{{ $prevDate }}">← 前日</button>

                <div class="date-select__display">
                <i class="fa-solid fa-calendar date-select__icon"></i>
                <span class="date-select__text">{{ $selectedDate }}</span>
                </div>

                <button class="date-select__btn" type="submit" name="target_date" value="{{ $nextDate }}">翌日 →</button>
                </form>
            --}}

            <form class="date-select" method="GET" action="{{ route("admin.attendances.index") }}">
                {{-- 前日 --}}
                <button class="date-select__button" type="submit" name="target_date" value="{{ $prevDate }}">← 前日</button>

                {{-- 日付表示（クリックで今日＝クエリ無し） --}}
                <div class="date-select__display">
                    <a href="{{ route("admin.attendances.index") }}" title="今日に戻る">
                        <i class="fa-solid fa-calendar"></i>
                        {{ \Carbon\Carbon::parse($selectedDate)->format("Y/m/d") }}
                    </a>
                </div>

                {{-- 翌日 --}}
                <button class="date-select__button" type="submit" name="target_date" value="{{ $nextDate }}">翌日 →</button>
            </form>
        </x-slot>

        {{-- 勤怠一覧テーブル --}}
        <x-index.table :headers="['名前', '出勤', '退勤', '休憩', '合計', '詳細']">
            @foreach ($attendances as $attendance)
                <tr>
                    <td>{{ $attendance->user->name }}</td>
                    <td>{{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format("H:i") : "" }}</td>
                    <td>{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format("H:i") : "" }}</td>
                    {{-- 合計（休憩） --}}
                    <td>
                        @if (! is_null($attendance->break_time))
                            @php
                                $hours = floor($attendance->break_time / 60);
                                $minutes = $attendance->break_time % 60;
                            @endphp

                            {{ sprintf("%02d:%02d", $hours, $minutes) }}
                        @endif
                    </td>

                    {{-- 合計（勤務時間） --}}
                    <td>
                        @if (! is_null($attendance->work_time))
                            @php
                                $hours = floor($attendance->work_time / 60);
                                $minutes = $attendance->work_time % 60;
                            @endphp

                            {{ sprintf("%02d:%02d", $hours, $minutes) }}
                        @endif
                    </td>
                    <td>
                        <a href="{{ route("admin.attendance.detail", $attendance->id) }}">詳細</a>
                    </td>
                </tr>
            @endforeach
        </x-index.table>
    </x-index.container>
@endsection
