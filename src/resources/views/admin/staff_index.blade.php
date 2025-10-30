@extends("layouts.app")

@push("styles")
    <link rel="stylesheet" href="{{ asset("css/list.css") }}" />
@endpush

@section("content")
    <x-index.container title="スタッフ一覧">
        <x-slot name="table">
            <x-index.table :headers="['名前', 'メールアドレス', '月次勤怠']">
                @foreach ($users as $user)
                    <tr class="index-table__row">
                        {{-- 名前 --}}
                        <td class="index-table__cell">
                            {{ $user->name }}
                        </td>
                        {{-- メールアドレス --}}
                        <td class="index-table__cell">
                            {{ $user->email }}
                        </td>
                        {{-- スタッフ別月次勤怠詳細リンク --}}
                        <td class="index-table__cell">
                            <a href="{{ route("admin.staff.attendance.index", $user->id) }}" class="index-table__link">詳細</a>
                        </td>
                    </tr>
                @endforeach
            </x-index.table>
        </x-slot>
    </x-index.container>
@endsection
