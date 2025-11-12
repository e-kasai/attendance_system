@extends("layouts.app")

@push("styles")
    <link rel="stylesheet" href="{{ asset("css/components/index.css") }}" />
@endpush

@section("content")
    {{-- roleによってタイトルを切り替え --}}
    @if (auth()->user()->role === "admin")
        @php
            $title = $user->name . "さんの勤怠";
        @endphp
    @else
        @php
            $title = "勤怠一覧";
        @endphp
    @endif
    <x-index.container :title="$title">
        <x-slot name="monthNav">
            <form
                class="month-select"
                method="GET"
                action="{{
                    auth()->user()->role === "admin"
                        ? route("admin.staff.attendance.index", $user->id)
                        : route("attendances.index")
                }}"
            >
                <button class="month-select__button" type="submit" name="target_ym" value="{{ $prevMonth }}">
                    <img class="month-select__arrow-icon" src="{{ asset("img/arrow_icon.png") }}" alt="logo" />
                    前月
                </button>
                <div class="month-select__display">
                    <img class="month-select__calender-icon" src="{{ asset("img/calender_icon.png") }}" alt="logo" />
                    <span class="month-select__text">{{ $selectedMonth }}</span>
                </div>
                <button class="month-select__button" type="submit" name="target_ym" value="{{ $nextMonth }}">
                    翌月
                    <img class="month-select__arrow-icon next" src="{{ asset("img/arrow_icon.png") }}" alt="logo" />
                </button>
            </form>
        </x-slot>

        <x-slot name="table">
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
                            @if (auth()->user()->role === "admin")
                                <a href="{{ route("admin.attendance.detail", $attendance->id) }}" class="index-table__link">
                                    詳細
                                </a>
                            @else
                                <a href="{{ route("attendance.detail", $attendance->id) }}" class="index-table__link">詳細</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-index.table>
        </x-slot>

        {{-- 管理者のみCSV出力ボタンを表示 --}}
        <x-slot name="btn">
            @if (auth()->user()->role === "admin")
                <div class="export-buttons">
                    <form method="GET" action="{{ route("admin.staff.attendance.export", ["id" => $user->id]) }}">
                        <input type="hidden" name="target_ym" value="{{ $targetYmForUrl }}" />
                        <button class="btn btn--export">CSV出力</button>
                    </form>
                </div>
            @endif
        </x-slot>
    </x-index.container>
@endsection
