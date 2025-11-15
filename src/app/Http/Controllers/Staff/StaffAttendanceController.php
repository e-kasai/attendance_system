<?php

namespace App\Http\Controllers\Staff;

use Illuminate\View\View;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Services\AttendanceService;


class StaffAttendanceController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function showAttendanceStatus(): View
    {
        $currentData = $this->getCurrentData();
        return view('staff.attendance_create', $currentData);
    }


    public function storeAttendanceStatus(Request $request)
    {
        $user = auth()->user();
        $action = $request->input('action');
        $this->attendanceService->handleAction($user, $action);

        // 更新後に再取得して表示
        $currentData = $this->getCurrentData();
        return view('staff.attendance_create', $currentData);
    }


    //プライベートメソッド:日時やラベルなど、UI共通データをまとめる
    private function getCurrentData(): array
    {
        $now   = Carbon::now();
        $today = $now->isoFormat('YYYY年M月D日(ddd)');
        $time =  $now->format('H:i');
        $user = auth()->user();
        $attendance = $user->todayAttendance();
        $status = optional($attendance)->status ?? 1;

        $statusLabel = [
            1 => '勤務外',
            2 => '出勤中',
            3 => '休憩中',
            4 => '退勤済',
        ];

        return compact('today', 'time', 'status', 'statusLabel', 'user', 'attendance');
    }
}
