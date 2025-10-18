<?php

namespace App\Http\Controllers\Staff;

use Illuminate\View\View;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;


class StaffAttendanceController extends Controller
{
    public function showAttendanceStatus(): View
    {
        Carbon::setLocale('ja');
        $today = Carbon::now()->isoFormat('YYYY年M月D日(ddd)');
        $time = Carbon::now()->format('H:i');

        $user = auth()->user();
        $attendance = $user->todayAttendance();

        $status = $attendance->status ?? 1;

        $statusLabel = [
            1 => '勤務外',
            2 => '出勤中',
            3 => '休憩中',
            4 => '退勤済',
        ];
        return view('staff.attendance_create', compact('today', 'time', 'status', 'statusLabel'));
    }


    public function storeAttendanceStatus(Request $request)
    {
        $statusLabel = [
            1 => '勤務外',
            2 => '出勤中',
            3 => '休憩中',
            4 => '退勤済',
        ];

        Carbon::setLocale('ja');
        $today = Carbon::now()->isoFormat('YYYY年M月D日(ddd)');
        $time = Carbon::now()->format('H:i');
        $user = auth()->user();
        $attendance = $user->todayAttendance();


        $action = $request->input('action');
        match ($action) {
            'work_start' => $user->startWork(),
            'work_end' => $user->endWork(),

            'break_start' => $attendance?->startBreak(),
            'break_end' => $attendance?->endBreak(),

            default => null,
        };

        // 更新後に再取得して表示
        $attendance = $user->todayAttendance();
        $status = $attendance->status ?? 1;

        return view('staff.attendance_create', compact('today', 'time', 'status', 'statusLabel'));
    }
}
