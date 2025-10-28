@extends("layouts.app")

@push("styles")
    <link rel="stylesheet" href="{{ asset("css/list.css") }}" />
@endpush

@section("content")
    <x-index.container title="申請一覧">
        <x-slot name="table">
            {{-- 状態切り替えタブ --}}
            <div class="tabs">
                <a
                    href="{{ route("requests.index", ["status" => "pending"]) }}"
                    class="tab {{ $status === "pending" ? "active" : "" }}"
                >
                    承認待ち
                </a>

                <a
                    href="{{ route("requests.index", ["status" => "approved"]) }}"
                    class="tab {{ $status === "approved" ? "active" : "" }}"
                >
                    承認済み
                </a>
            </div>
            <x-index.table :headers="['状態', '名前', '対象日時', '申請理由', '申請日時', '詳細']">
                @forelse ($updateRequests as $updateRequest)
                    @php
                        $attendance = $updateRequest->attendance;
                    @endphp

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
                        {{--
                            <td class="index-table__cell">
                            {{ $attendance->user->name ?? "" }}
                            </td>
                        --}}

                        {{-- 管理者は申請者の名前、スタッフは自分の名前 --}}
                        <td>
                            @if (! empty($isAdmin))
                                {{ $updateRequest->attendance->user->name ?? "-" }}
                            @else
                                {{ auth()->user()->name }}
                            @endif
                        </td>

                        {{-- 対象日時 --}}
                        <td class="index-table__cell">
                            {{ optional($attendance->date)->format("Y/m/d") }}
                        </td>

                        {{-- 申請理由 --}}
                        <td class="index-table__cell">
                            {{ $updateRequest->comment ?? "" }}
                        </td>

                        {{-- 申請日時 --}}
                        <td class="index-table__cell">
                            {{ $updateRequest->created_at->format("Y/m/d") }}
                        </td>

                        {{-- 詳細リンク --}}
                        <td class="index-table__cell">
                            {{--
                                <a href="{{ route("attendance.detail", $updateRequest->attendance_id) }}" class="index-table__link">
                                詳細
                                </a>
                            --}}

                            {{--
                                <a
                                href="{{
                                route("attendance.detail", [
                                "id" => $updateRequest->attendance_id,
                                "from" => "request",
                                "update_id" => $updateRequest->id,
                                ])
                                }}"
                                >
                                詳細
                                </a>
                            --}}

                            @if (auth()->user()->role === "admin")
                                <a
                                    href="{{ route("admin.request.approve.show", ["attendance_correct_request_id" => $updateRequest->id]) }}"
                                >
                                    詳細
                                </a>
                            @else
                                <a
                                    href="{{
                                        route("attendance.detail", [
                                            "id" => $updateRequest->attendance_id,
                                            "from" => "request",
                                            "update_id" => $updateRequest->id,
                                        ])
                                    }}"
                                >
                                    詳細
                                </a>
                            @endif
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
