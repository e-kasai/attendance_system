@extends("layouts.app")

@push("styles")
    <link rel="stylesheet" href="{{ asset("css/list.css") }}" />
@endpush

@section("content")
    <x-index.container title="申請一覧">
        <x-slot name="table">
            <x-index.table :headers="['状態', '名前', '対象日時', '申請理由', '申請日時', '詳細']">
                @forelse ($updateRequests as $updateRequest)
                    @php
                        $attendance = $updateRequest->attendance;
                    @endphp

                    {{-- 状態切り替えタブ --}}

                    <tr class="index-table__row">
                        {{-- 状態 --}}
                        <td class="index-table__cell">
                            @switch($updateRequest->approval_status)
                                @case(1)
                                    <span class="status pending">承認待ち</span>

                                    @break
                                @case(2)
                                    <span class="status approved">承認済み</span>

                                    @break
                                @default
                                    <span>-</span>
                            @endswitch
                        </td>
                        {{-- 名前 --}}
                        <td class="index-table__cell">
                            {{ $attendance->user->name ?? "" }}
                        </td>
                        {{-- 対象日時 --}}
                        <td class="index-table__cell">
                            {{ optional($attendance->date)->format("Y-m-d") }}
                        </td>

                        {{-- 申請理由 --}}
                        <td class="index-table__cell">
                            {{ $updateRequest->comment ?? "" }}
                        </td>

                        {{-- 申請日時 --}}
                        <td class="index-table__cell">
                            {{ $updateRequest->created_at->format("Y-m-d") }}
                        </td>

                        {{-- 詳細リンク --}}
                        <td class="index-table__cell">
                            <a href="{{ route("attendance.detail", $updateRequest->attendance_id) }}" class="index-table__link">
                                詳細
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="index-table__empty">データがありません</td>
                    </tr>
                @endforelse
            </x-index.table>
        </x-slot>
    </x-index.container>
@endsection
